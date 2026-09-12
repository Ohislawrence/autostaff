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
    public function search(string $query, int $limit = 10, ?int $organizationId = null): array
    {
        [$provider, $key] = $this->resolveCredentials($organizationId);

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

    public function isConfigured(?int $organizationId = null): bool
    {
        [$provider, $key] = $this->resolveCredentials($organizationId);

        return $provider !== 'none' && ! empty($key);
    }

    /**
     * Resolve the active search provider + key for a tenant.
     *
     * Precedence:
     *  1. Tenant explicitly configured "platform" → platform shared credentials.
     *  2. Tenant configured their own Serper/Brave key → tenant credentials.
     *  3. Tenant explicitly configured "none" → no search.
     *  4. No tenant row yet → platform shared credentials (sensible default).
     *
     * @return array{0: string, 1: ?string} [provider, api_key]
     */
    protected function resolveCredentials(?int $organizationId): array
    {
        if ($organizationId) {
            $tenant = \App\Models\TenantSearchSettings::where('organization_id', $organizationId)->first();

            if ($tenant) {
                if ($tenant->provider === \App\Models\TenantSearchSettings::PROVIDER_PLATFORM) {
                    return $this->platformCredentials();
                }

                if ($tenant->provider !== \App\Models\TenantSearchSettings::PROVIDER_NONE && ! empty($tenant->api_key)) {
                    return [$tenant->provider, $tenant->api_key];
                }

                return [\App\Models\TenantSearchSettings::PROVIDER_NONE, null];
            }
        }

        return $this->platformCredentials();
    }

    /**
     * @return array{0: string, 1: ?string} [provider, api_key]
     */
    protected function platformCredentials(): array
    {
        $settings = ProspectingSettings::instance();

        return [$settings->search_provider, $settings->search_api_key];
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
