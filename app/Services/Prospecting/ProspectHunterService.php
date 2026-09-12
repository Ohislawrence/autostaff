<?php

namespace App\Services\Prospecting;

use App\Ai\Providers\AiProviderInterface;
use App\Models\ProspectingCampaign;
use App\Models\Prospect;
use App\Support\AiJson;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ProspectHunterService
{
    public function __construct(
        protected WebSearchService $webSearch,
        protected AiProviderInterface $ai,
        protected ProspectingSettingsService $settings,
        protected ContactValidator $validator,
    ) {}

    /**
     * Hunt for ICP-matching prospects and store them under the campaign.
     */
    public function hunt(ProspectingCampaign $campaign): array
    {
        $limit = max(1, (int) ($campaign->daily_limit ?: 25));
        $created = 0;
        $sources = ['web_search' => 0, 'ai_generated' => 0];

        // 1. Live web search (only when a provider + key are configured).
        if ($this->webSearch->isConfigured($campaign->organization_id)) {
            foreach ($this->buildQueries($campaign) as $query) {
                $results = $this->webSearch->search($query, min($limit, 5), $campaign->organization_id);
                foreach ($results as $result) {
                    if ($created >= $limit) {
                        break 2;
                    }
                    $data = $this->normalizeSearchResult($result);
                    if ($this->upsert($campaign, $data, 'web_search')) {
                        $created++;
                        $sources['web_search']++;
                    }
                }
            }
        }

        // 2. DeepSeek-assisted generation (always available — uses the wired API).
        try {
            $aiProspects = $this->generateWithAi($campaign, max(1, $limit - $created));
            foreach ($aiProspects as $data) {
                if ($created >= $limit) {
                    break;
                }
                if ($this->upsert($campaign, $data, 'ai_generated')) {
                    $created++;
                    $sources['ai_generated']++;
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Prospecting AI generation failed', [
                'campaign_id' => $campaign->id,
                'error' => $e->getMessage(),
            ]);
        }

        $campaign->update([
            'status' => in_array($campaign->status, ['draft', 'paused']) ? 'active' : $campaign->status,
            'last_run_at' => now(),
        ]);

        return [
            'created' => $created,
            'sources' => $sources,
        ];
    }

    protected function buildQueries(ProspectingCampaign $campaign): array
    {
        $icp = $campaign->icp ?? [];
        $persona = $campaign->buyer_persona_snapshot ?? [];
        $industry = (array) ($icp['industry'] ?? []);
        $titles = array_merge((array) ($icp['job_titles'] ?? []), (array) ($persona['role_titles'] ?? []));
        $geo = (array) ($icp['geography'] ?? []);
        $keywords = array_merge((array) ($icp['keywords'] ?? []), (array) ($persona['keywords'] ?? []));

        $parts = array_values(array_filter([
            implode(' ', array_slice($keywords, 0, 2)),
            implode(' ', array_slice($industry, 0, 1)),
            implode(' ', array_slice($titles, 0, 2)),
            implode(' ', array_slice($geo, 0, 1)),
        ]));

        if (empty($parts)) {
            return [];
        }

        $queries = [];
        foreach ($parts as $part) {
            $others = array_values(array_diff($parts, [$part]));
            $queries[] = trim($part . ' ' . ($others[0] ?? ''));
            if (count($queries) >= 3) {
                break;
            }
        }

        return array_values(array_unique(array_filter($queries)));
    }

    protected function normalizeSearchResult(array $result): array
    {
        $title = trim($result['title'] ?? '');
        $url = $result['url'] ?? '';
        $host = parse_url($url, PHP_URL_HOST) ?: '';

        $name = null;
        $company = null;
        if (str_contains($title, '-')) {
            [$left, $right] = explode('-', $title, 2);
            $company = trim($left);
            $name = trim(explode('|', $right)[0]);
        } elseif (str_contains($title, '|')) {
            $company = trim(explode('|', $title)[0]);
        } else {
            $company = $title;
        }

        $enriched = $this->enrichFromUrl($url);

        return [
            'name' => $enriched['name'] ?: $name,
            'email' => $enriched['email'] ?? null,
            'title' => null,
            'company' => $enriched['company'] ?: $company,
            'website' => $enriched['website'] ?: $host,
            'linkedin_url' => $enriched['linkedin_url'] ?? null,
            'source_url' => $url,
            'metadata' => ['snippet' => $result['snippet'] ?? '', 'url' => $url],
        ];
    }

    /**
     * Lightweight page enrichment: extract the first email + LinkedIn URL from a page.
     */
    protected function enrichFromUrl(string $url): array
    {
        if ($url === '' || ! preg_match('/^https?:\/\//i', $url)) {
            return [];
        }

        try {
            $response = Http::timeout(6)->withHeaders([
                'User-Agent' => 'Mozilla/5.0 (compatible; AutostaffProspecting/1.0)',
            ])->get($url);

            if (! $response->successful()) {
                return [];
            }

            $html = $response->body();

            $email = null;
            if (preg_match('/[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}/i', $html, $m)) {
                $email = strtolower($m[0]);
                if (str_contains($email, '.png') || str_contains($email, '.jpg') || str_contains($email, '.svg')) {
                    $email = null;
                }
            }

            $linkedin = null;
            if (preg_match('/https?:\/\/(?:www\.)?linkedin\.com\/[^\s"\'<>]+/i', $html, $m)) {
                $linkedin = rtrim($m[0], '.');
            }

            $company = null;
            if (preg_match('/<title[^>]*>(.*?)<\/title>/is', $html, $m)) {
                $company = trim(html_entity_decode($m[1]));
                $company = Str::limit($company, 120, '');
            }

            return [
                'email' => $email,
                'linkedin_url' => $linkedin,
                'company' => $company,
                'website' => parse_url($url, PHP_URL_HOST) ?: null,
            ];
        } catch (\Throwable $e) {
            return [];
        }
    }

    protected function generateWithAi(ProspectingCampaign $campaign, int $count): array
    {
        $count = min(20, max(1, $count));
        $icp = json_encode($campaign->icp ?? [], JSON_PRETTY_PRINT);
        $offer = $campaign->offer ?: 'N/A';

        $prompt = <<<PROMPT
You are a B2B lead researcher. Based on the Ideal Customer Profile below, produce a JSON array of {$count} realistic companies/contacts that match.

Ideal Customer Profile:
{$icp}

Offer being pitched:
{$offer}

Requirements:
- Return ONLY valid JSON: an array of objects.
- Each object MUST use these keys: name, email, title, company, company_size, industry, location, website, linkedin_url.
- Prefer publicly-known businesses. Emails must be plausible business addresses; if uncertain, use a clearly role-based address and add no extra text.
- Values may be empty strings but keys must exist.
- Do not wrap the JSON in markdown fences.
PROMPT;

        $options = [
            'temperature' => 0.4,
            'max_tokens' => 3000,
        ];
        if ($model = $this->settings->deepseekModel()) {
            $options['model'] = $model;
        }

        $response = $this->ai->chat([
            ['role' => 'system', 'content' => 'You return strict JSON only.'],
            ['role' => 'user', 'content' => $prompt],
        ], $options);

        $data = AiJson::parse($response->content);

        if ($data === null) {
            return [];
        }

        $items = isset($data[0]) ? $data : ($data['prospects'] ?? []);
        $items = is_array($items) ? array_values($items) : [];

        return collect($items)->filter(fn ($p) => is_array($p))->map(function ($p) {
            return [
                'name' => $p['name'] ?? null,
                'email' => $p['email'] ?? null,
                'title' => $p['title'] ?? null,
                'company' => $p['company'] ?? null,
                'company_size' => $p['company_size'] ?? null,
                'industry' => $p['industry'] ?? null,
                'location' => $p['location'] ?? null,
                'website' => $p['website'] ?? null,
                'linkedin_url' => $p['linkedin_url'] ?? null,
                'source_url' => null,
                'metadata' => ['ai_generated' => true, 'needs_verification' => true],
            ];
        })->all();
    }

    protected function upsert(ProspectingCampaign $campaign, array $data, string $source): bool
    {
        $email = isset($data['email']) ? strtolower(trim((string) $data['email'])) : null;
        $website = isset($data['website']) ? strtolower(trim((string) $data['website'])) : null;

        if ($email) {
            $exists = Prospect::where('campaign_id', $campaign->id)->where('email', $email)->exists();
        } elseif ($website) {
            $exists = Prospect::where('campaign_id', $campaign->id)->where('website', $website)->exists();
        } else {
            return false;
        }

        if ($exists) {
            return false;
        }

        if (! $this->passesSourcingRules($campaign, $source, $data)) {
            return false;
        }

        $subjectType = $this->classifySubject($email, $data['company'] ?? null);

        $prospect = Prospect::create([
            'campaign_id' => $campaign->id,
            'organization_id' => $campaign->organization_id,
            'name' => $data['name'] ?? null,
            'email' => $email,
            'title' => $data['title'] ?? null,
            'company' => $data['company'] ?? null,
            'company_size' => $data['company_size'] ?? null,
            'industry' => $data['industry'] ?? null,
            'location' => $data['location'] ?? null,
            'website' => $website,
            'linkedin_url' => $data['linkedin_url'] ?? null,
            'source' => $source,
            'source_url' => $data['source_url'] ?? null,
            'status' => 'new',
            'metadata' => $data['metadata'] ?? null,
            'provenance' => $this->buildProvenance($campaign, $source, $data),
            'data_subject_type' => $subjectType,
            'legal_basis' => $subjectType === 'corporate' ? $this->settings->defaultLegalBasis() : null,
        ]);

        $validation = $this->validator->validate($email);
        $prospect->update([
            'validation_status' => $validation['status'],
            'validation_details' => $validation['details'],
            'validated_at' => now(),
        ]);

        $prospect->events()->create([
            'organization_id' => $campaign->organization_id,
            'type' => 'discovered',
            'payload' => ['source' => $source, 'validation' => $validation['status']],
        ]);

        return true;
    }

    protected function passesSourcingRules(ProspectingCampaign $campaign, string $source, array $data): bool
    {
        $rules = $campaign->sourcing_rules ?? [];

        $blockedSources = (array) ($rules['blocked_sources'] ?? []);
        if (in_array($source, $blockedSources, true)) {
            return false;
        }

        if ($source === 'ai_generated' && ($rules['allow_ai_generated'] ?? true) === false) {
            return false;
        }

        $blockedRegions = array_map('strtolower', (array) ($rules['blocked_regions'] ?? []));
        $location = strtolower((string) ($data['location'] ?? ''));
        foreach ($blockedRegions as $region) {
            if ($region !== '' && str_contains($location, $region)) {
                return false;
            }
        }

        return true;
    }

    protected function classifySubject(?string $email, ?string $company): string
    {
        if ($company) {
            return 'corporate';
        }

        if ($email && $this->validator->isFreeDomain($email)) {
            return 'individual';
        }

        return 'unknown';
    }

    protected function buildProvenance(ProspectingCampaign $campaign, string $source, array $data): array
    {
        return [
            'source' => $source,
            'source_url' => $data['source_url'] ?? null,
            'campaign' => $campaign->name,
            'ai_generated' => $source === 'ai_generated',
            'needs_verification' => $source === 'ai_generated',
        ];
    }
}
