<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Services\Prospecting\ProspectingSettingsService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProspectingSettingsController extends Controller
{
    public function __construct(protected ProspectingSettingsService $settings) {}

    public function index()
    {
        $s = $this->settings->get();

        return Inertia::render('Platform/Prospecting/Settings', [
            'settings' => [
                'telegram_bot_token_masked' => $this->mask($this->settings->telegramToken()),
                'telegram_chat_id' => $this->settings->telegramChatId(),
                'alert_email' => $this->settings->alertEmail(),
                'sender_name' => $this->settings->senderName(),
                'sender_email' => $this->settings->senderEmail(),
                'signature' => $s->signature,
                'deepseek_model' => $s->deepseek_model,
                'search_provider' => $s->search_provider,
                'search_api_key_masked' => $this->mask($s->search_api_key),
                'daily_hunt_limit' => $s->daily_hunt_limit,
                'daily_outreach_limit' => $s->daily_outreach_limit,
                'qualification_threshold' => $s->qualification_threshold,
                'auto_hunt' => (bool) $s->auto_hunt,
                'auto_outreach' => (bool) $s->auto_outreach,
                'has_telegram_token' => ! empty($this->settings->telegramToken()),
                'has_search_key' => ! empty($s->search_api_key),
                'postal_address' => $s->postal_address,
                'support_email' => $s->support_email,
                'default_legal_basis' => $this->settings->defaultLegalBasis(),
                'max_emails_per_hour' => $this->settings->maxEmailsPerHour(),
                'require_approval_ai_contacts' => $this->settings->requireApprovalAiContacts(),
            ],
            'webhookUrl' => route('prospecting.reply', ['token' => $s->reply_webhook_secret]),
            'webhookSecret' => $s->reply_webhook_secret,
            'deliveryWebhookUrl' => route('prospecting.delivery', ['token' => $s->reply_webhook_secret]),
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'telegram_bot_token' => 'nullable|string|max:255',
            'telegram_chat_id' => 'nullable|string|max:255',
            'alert_email' => 'nullable|email|max:255',
            'sender_name' => 'nullable|string|max:255',
            'sender_email' => 'nullable|email|max:255',
            'signature' => 'nullable|string',
            'deepseek_model' => 'nullable|string|max:255',
            'search_provider' => 'nullable|string|in:none,serper,brave',
            'search_api_key' => 'nullable|string|max:255',
            'daily_hunt_limit' => 'nullable|integer|min:1|max:1000',
            'daily_outreach_limit' => 'nullable|integer|min:1|max:1000',
            'qualification_threshold' => 'nullable|integer|min:1|max:10',
            'auto_hunt' => 'boolean',
            'auto_outreach' => 'boolean',
            'postal_address' => 'nullable|string',
            'support_email' => 'nullable|email|max:255',
            'default_legal_basis' => 'nullable|string|in:legitimate_interest,consent',
            'max_emails_per_hour' => 'nullable|integer|min:1|max:1000',
            'require_approval_ai_contacts' => 'boolean',
        ]);

        if ($request->boolean('regenerate_webhook_secret')) {
            $this->settings->get()->regenerateWebhookSecret();
        }

        $this->settings->save($validated);

        return back()->with('success', 'Prospecting settings saved.');
    }

    protected function mask(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        $len = strlen($value);
        if ($len <= 8) {
            return str_repeat('•', $len);
        }

        return substr($value, 0, 4) . str_repeat('•', $len - 8) . substr($value, -4);
    }
}
