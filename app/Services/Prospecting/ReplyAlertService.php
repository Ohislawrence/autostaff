<?php

namespace App\Services\Prospecting;

use App\Models\Prospect;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class ReplyAlertService
{
    public function __construct(
        protected ProspectingSettingsService $settings,
        protected TelegramNotifier $telegram,
        protected SuppressionService $suppression,
    ) {}

    /**
     * Record an inbound reply and immediately alert the platform owner
     * via Telegram and email ("the exact second a prospect replies").
     */
    public function recordReply(Prospect $prospect, string $from, string $subject, string $body, array $meta = []): array
    {
        $prospect->messages()->create([
            'campaign_id' => $prospect->campaign_id,
            'organization_id' => $prospect->organization_id,
            'direction' => 'inbound',
            'pass' => 0,
            'subject' => $subject,
            'body' => $body,
            'status' => 'received',
            'metadata' => $meta,
        ]);

        // Honor opt-out / unsubscribe requests received as replies.
        if ($this->looksLikeUnsubscribe($subject . ' ' . $body)) {
            $this->suppression->suppress($prospect->email, 'unsubscribe', $prospect->organization_id, $prospect->campaign_id, 'reply');
            $prospect->update([
                'status' => 'unsubscribed',
                'suppressed_at' => now(),
                'suppression_reason' => 'unsubscribe',
            ]);
            $prospect->events()->create([
                'organization_id' => $prospect->organization_id,
                'type' => 'unsubscribed',
                'payload' => ['via' => 'reply'],
            ]);

            return ['unsubscribed' => true];
        }

        $prospect->update([
            'status' => 'replied',
            'replied_at' => $prospect->replied_at ?? now(),
            'last_reply_at' => now(),
            'reply_summary' => Str::limit($body, 500, ''),
        ]);

        return $this->dispatchAlerts($prospect, $from, $subject, $body);
    }

    protected function looksLikeUnsubscribe(string $text): bool
    {
        $text = strtolower($text);

        foreach (['unsubscribe', 'opt out', 'opt-out', 'remove me', 'do not contact', 'stop emailing'] as $needle) {
            if (str_contains($text, $needle)) {
                return true;
            }
        }

        return false;
    }

    public function dispatchAlerts(Prospect $prospect, string $from, string $subject, string $body): array
    {
        $results = ['telegram' => false, 'email' => false];

        $telegramText = $this->telegramText($prospect, $from, $subject, $body);
        $results['telegram'] = $this->telegram->send($telegramText);

        $email = $this->settings->alertEmail();
        if ($email) {
            try {
                Mail::raw($this->emailText($prospect, $from, $subject, $body), function ($message) use ($email, $prospect) {
                    $message->to($email)
                        ->subject('💬 Prospect reply: ' . ($prospect->name ?: $prospect->company ?: $prospect->email))
                        ->from($this->settings->senderEmail(), $this->settings->senderName());
                });
                $results['email'] = true;
            } catch (\Throwable $e) {
                Log::warning('Prospecting reply email alert failed', ['error' => $e->getMessage()]);
            }
        }

        return $results;
    }

    protected function telegramText(Prospect $prospect, string $from, string $subject, string $body): string
    {
        $name = htmlspecialchars($prospect->name ?: $prospect->email ?: 'Unknown');
        $company = htmlspecialchars($prospect->company ?: 'N/A');
        $score = (int) $prospect->score;
        $campaign = htmlspecialchars($prospect->campaign->name ?? 'Campaign');
        $preview = htmlspecialchars(Str::limit($body, 300, ''));

        return "🚀 <b>Prospect replied!</b>\n"
            . "👤 {$name} — {$company}\n"
            . "🎯 Score: {$score}/10 · Campaign: {$campaign}\n\n"
            . "Subject: " . htmlspecialchars($subject) . "\n\n"
            . "{$preview}";
    }

    protected function emailText(Prospect $prospect, string $from, string $subject, string $body): string
    {
        return "A prospect replied and needs a human follow-up.\n\n"
            . "Prospect: {$prospect->name} <{$prospect->email}>\n"
            . "Company: {$prospect->company}\n"
            . "Score: {$prospect->score}/10\n"
            . "Campaign: {$prospect->campaign->name}\n\n"
            . "From: {$from}\n"
            . "Subject: {$subject}\n\n"
            . "Message:\n{$body}";
    }
}
