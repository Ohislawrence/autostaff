<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Prospect;
use App\Models\ProspectingSettings;
use App\Services\Prospecting\ReplyAlertService;
use Illuminate\Http\Request;

class ProspectingReplyWebhookController extends Controller
{
    /**
     * Inbound reply webhook. Pings Telegram/email the moment a prospect replies.
     */
    public function __invoke(Request $request, string $token, ReplyAlertService $alerts)
    {
        $settings = ProspectingSettings::instance();
        $secret = $settings->reply_webhook_secret ?: config('services.prospecting.reply_webhook_secret');

        if (! $secret || ! hash_equals((string) $secret, (string) $token)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $payload = $request->all();

        $from = $this->extractFrom($payload);
        $subject = $payload['subject'] ?? ($payload['Subject'] ?? '');
        $body = $payload['body']
            ?? ($payload['text'] ?? ($payload['TextBody'] ?? ($payload['stripped-text'] ?? ($payload['stripped-html'] ?? ''))));

        if (empty($body) && ! empty($payload['html'])) {
            $body = strip_tags((string) $payload['html']);
        }

        $email = strtolower(trim((string) $from));
        $prospect = Prospect::where('email', $email)->first();

        if (! $prospect) {
            return response()->json(['status' => 'ignored', 'reason' => 'unknown sender']);
        }

        $alerts->recordReply($prospect, $from, (string) $subject, (string) $body, [
            'source' => 'webhook',
            'provider' => $this->detectProvider($payload),
        ]);

        return response()->json(['status' => 'ok']);
    }

    protected function extractFrom(array $payload): ?string
    {
        if (! empty($payload['from'])) {
            return is_array($payload['from']) ? ($payload['from']['email'] ?? $payload['from'][0] ?? null) : $payload['from'];
        }
        if (! empty($payload['From'])) {
            return $payload['From'];
        }
        if (! empty($payload['FromEmail'])) {
            return $payload['FromEmail'];
        }
        if (! empty($payload['sender'])) {
            return $payload['sender'];
        }
        if (! empty($payload['envelope']['from'])) {
            return $payload['envelope']['from'];
        }

        return null;
    }

    protected function detectProvider(array $payload): string
    {
        if (isset($payload['stripped-text']) || isset($payload['X-Mailgun-Recipient'])) {
            return 'mailgun';
        }
        if (isset($payload['TextBody']) || isset($payload['HtmlBody'])) {
            return 'postmark';
        }
        if (isset($payload['event-data']) || isset($payload['event_data'])) {
            return 'mailgun';
        }

        return 'generic';
    }
}
