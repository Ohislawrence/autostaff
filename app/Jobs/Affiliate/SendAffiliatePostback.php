<?php

namespace App\Jobs\Affiliate;

use App\Models\AffiliateConversion;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendAffiliatePostback implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 4;

    public array $backoff = [15, 60, 180];

    public function __construct(public int $conversionId) {}

    public function handle(): void
    {
        $conversion = AffiliateConversion::find($this->conversionId);

        if (! $conversion || $conversion->status === 'sent') {
            return;
        }

        $conversion->increment('attempts');

        try {
            $response = Http::timeout(15)->get($this->buildPostbackUrl($conversion));

            $conversion->update([
                'response_code' => $response->status(),
                'response_body' => mb_substr($response->body(), 0, 4000),
            ]);

            if ($response->successful()) {
                $conversion->update(['status' => 'sent', 'sent_at' => now()]);

                return;
            }

            $conversion->update(['status' => 'failed']);

            Log::warning('Affiliate postback returned a non-success status', [
                'conversion_id' => $conversion->id,
                'status' => $response->status(),
            ]);
        } catch (\Throwable $e) {
            $conversion->update([
                'status' => 'failed',
                'response_body' => mb_substr($e->getMessage(), 0, 4000),
            ]);

            Log::warning('Affiliate postback request failed', [
                'conversion_id' => $conversion->id,
                'error' => $e->getMessage(),
            ]);
        }

        // Re-throw so the queue retries transient failures up to $tries.
        throw new \RuntimeException('Affiliate postback failed.');
    }

    public function failed(\Throwable $e): void
    {
        $conversion = AffiliateConversion::find($this->conversionId);
        $conversion?->update([
            'status' => 'failed',
            'response_body' => mb_substr($e->getMessage(), 0, 4000),
        ]);
    }

    protected function buildPostbackUrl(AffiliateConversion $conversion): string
    {
        $template = (string) config('services.clicksintel.postback_url', '');

        if ($template === '') {
            throw new \RuntimeException('ClicksIntel postback URL is not configured (CLICKSINTEL_POSTBACK_URL).');
        }

        $macros = [
            '{click_id}' => (string) $conversion->click_id,
            '{sub_id}' => (string) $conversion->sub_id,
            '{payout}' => (string) $conversion->payout_amount,
            '{currency}' => (string) $conversion->currency,
            '{txn_id}' => (string) $conversion->uuid,
            '{event}' => (string) $conversion->event,
            '{sale_amount}' => (string) $conversion->sale_amount,
            '{plan}' => (string) $conversion->plan_slug,
        ];

        $url = strtr($template, $macros);

        // If the URL used macros, return as-is; otherwise append query params
        // using the configured parameter names.
        if (str_contains($template, '{')) {
            return $url;
        }

        $params = [
            (string) config('services.clicksintel.click_param', 'click_id') => $conversion->click_id,
            (string) config('services.clicksintel.sub_param', 'sub_id') => $conversion->sub_id,
            (string) config('services.clicksintel.transaction_param', 'txn_id') => $conversion->uuid,
            'payout' => $conversion->payout_amount,
            'currency' => $conversion->currency,
            'event' => $conversion->event,
        ];

        $params = array_filter($params, fn ($value) => $value !== null && $value !== '');

        $separator = str_contains($url, '?') ? '&' : '?';

        return $url.$separator.http_build_query($params);
    }
}
