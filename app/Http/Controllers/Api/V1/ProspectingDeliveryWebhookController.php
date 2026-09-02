<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Prospect;
use App\Models\ProspectingSettings;
use App\Services\Prospecting\SuppressionService;
use Illuminate\Http\Request;

class ProspectingDeliveryWebhookController extends Controller
{
    public function __invoke(Request $request, string $token, SuppressionService $suppression)
    {
        $settings = ProspectingSettings::instance();
        $secret = $settings->reply_webhook_secret ?: config('services.prospecting.reply_webhook_secret');

        if (! $secret || ! hash_equals((string) $secret, (string) $token)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $payload = $request->all();
        $type = $this->detectType($payload);
        $email = strtolower(trim((string) $this->extractEmail($payload)));

        $prospect = Prospect::where('email', $email)->first();
        if (! $prospect) {
            return response()->json(['status' => 'ignored', 'reason' => 'unknown recipient']);
        }

        if ($type === 'complaint' || $this->isHardBounce($payload)) {
            $suppression->suppress($email, $type, $prospect->organization_id, $prospect->campaign_id, 'webhook');
            $prospect->update([
                'status' => $type === 'complaint' ? 'unsubscribed' : 'bounced',
                'suppressed_at' => now(),
                'suppression_reason' => $type,
            ]);
            $prospect->events()->create([
                'organization_id' => $prospect->organization_id,
                'type' => $type === 'complaint' ? 'complained' : 'bounced',
                'payload' => ['provider' => $this->detectProvider($payload)],
            ]);
        }

        $prospect->messages()->where('direction', 'outbound')->latest()->first()?->update([
            $type === 'complaint' ? 'complained_at' : 'bounced_at' => now(),
        ]);

        return response()->json(['status' => 'ok', 'type' => $type]);
    }

    protected function detectType(array $payload): string
    {
        $event = strtolower((string) ($payload['event'] ?? $payload['eventType'] ?? $payload['RecordType'] ?? ''));

        if (str_contains($event, 'complaint') || str_contains($event, 'spam')) {
            return 'complaint';
        }

        return 'bounce';
    }

    protected function isHardBounce(array $payload): bool
    {
        $bounceType = strtolower((string) ($payload['bounceType'] ?? ($payload['bounce']['bounceType'] ?? '')));
        $type = strtolower((string) ($payload['Type'] ?? ($payload['bounce']['bounceType'] ?? '')));

        return str_contains($bounceType, 'permanent') || str_contains($type, 'hardbounce');
    }

    protected function extractEmail(array $payload): ?string
    {
        foreach (['recipient', 'email', 'Email', 'Recipient', 'to'] as $key) {
            if (! empty($payload[$key])) {
                return $payload[$key];
            }
        }

        return $payload['mail']['destination'][0]
            ?? $payload['bounce']['bouncedRecipients'][0]['emailAddress']
            ?? $payload['complaint']['complainedRecipients'][0]['emailAddress']
            ?? null;
    }

    protected function detectProvider(array $payload): string
    {
        if (isset($payload['event-data']) || isset($payload['event'])) {
            return 'mailgun';
        }
        if (isset($payload['RecordType'])) {
            return 'postmark';
        }
        if (isset($payload['eventType'])) {
            return 'ses';
        }

        return 'generic';
    }
}
