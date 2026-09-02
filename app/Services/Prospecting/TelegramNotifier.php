<?php

namespace App\Services\Prospecting;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramNotifier
{
    public function __construct(protected ProspectingSettingsService $settings) {}

    /**
     * Send a plain-text (HTML-parsed) Telegram message to the configured chat.
     */
    public function send(string $text): bool
    {
        $token = $this->settings->telegramToken();
        $chatId = $this->settings->telegramChatId();

        if (empty($token) || empty($chatId)) {
            Log::debug('Telegram alert skipped: bot token or chat id not configured.');

            return false;
        }

        try {
            $response = Http::timeout(10)
                ->post("https://api.telegram.org/bot{$token}/sendMessage", [
                    'chat_id' => $chatId,
                    'text' => $text,
                    'parse_mode' => 'HTML',
                    'disable_web_page_preview' => true,
                ]);

            if (! $response->successful()) {
                Log::warning('Telegram alert failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::warning('Telegram alert error', ['error' => $e->getMessage()]);

            return false;
        }
    }
}
