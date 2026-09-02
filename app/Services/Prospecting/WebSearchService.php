<?php

namespace App\Services\Prospecting;

use App\Models\ProspectingSettings;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WebSearchService
{
    /**
     * Perform a web search using the configured provider.
     *
     * @return array<int, array{title: string, url: string, snippet: string}>
     */
    public function search(string $query, int $limit = 10): array
    {
        $settings = ProspectingSettings::instance();
        $provider = $settings->search_provider;
        $key = $settings->search_api_key;

        if ($provider === 'none' || empty($key)) {
            return [];
        }

        try {
            return match ($provider) {
                'serper' => $this->serper($query, $limit, $key),
                'brave' => $this->brave($query, $limit, $key),
                default => [],
            };
        } catch (\Throwable $e) {
            Log::warning('Prospecting web search failed', [
                'provider' => $provider,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    public function isConfigured(): bool
    {
        $settings = ProspectingSettings::instance();

        return $settings->search_provider !== 'none' && ! empty($settings->search_api_key);
    }

    protected function serper(string $query, int $limit, string $key): array
    {
        $response = Http::timeout(15)
            ->withHeaders(['X-API-KEY' => $key])
            ->post('https://google.serper.dev/search', [
                'q' => $query,
                'num' => min(10, max(1, $limit)),
            ]);

        if (! $response->successful()) {
            return [];
        }

        $data = $response->json();

        return collect($data['organic'] ?? [])->map(fn ($r) => [
            'title' => $r['title'] ?? '',
            'url' => $r['link'] ?? '',
            'snippet' => $r['snippet'] ?? '',
        ])->filter(fn ($r) => $r['url'] !== '')->values()->all();
    }

    protected function brave(string $query, int $limit, string $key): array
    {
        $response = Http::timeout(15)
            ->withHeaders([
                'Accept' => 'application/json',
                'X-Subscription-Token' => $key,
            ])
            ->get('https://api.search.brave.com/res/v1/web/search', [
                'q' => $query,
                'count' => min(20, max(1, $limit)),
            ]);

        if (! $response->successful()) {
            return [];
        }

        $data = $response->json();

        return collect($data['web']['results'] ?? [])->map(fn ($r) => [
            'title' => $r['title'] ?? '',
            'url' => $r['url'] ?? '',
            'snippet' => $r['description'] ?? '',
        ])->filter(fn ($r) => $r['url'] !== '')->values()->all();
    }
}
