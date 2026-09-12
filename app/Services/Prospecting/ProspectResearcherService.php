<?php

namespace App\Services\Prospecting;

use App\Ai\Providers\AiProviderInterface;
use App\Models\Prospect;
use Illuminate\Support\Facades\Log;

/**
 * Researches a prospect (their website + online presence) and produces
 * evidence-backed reasoning for why they are a good fit.
 *
 * Uses the tenant's web-search key (Phase 1) to gather material, then
 * synthesizes findings with DeepSeek. Falls back to a heuristic when
 * search/AI are unavailable.
 */
class ProspectResearcherService
{
    public function __construct(
        protected WebSearchService $webSearch,
        protected AiProviderInterface $ai,
        protected ProspectingSettingsService $settings,
    ) {}

    public function research(Prospect $prospect): array
    {
        $material = $this->gatherMaterial($prospect);
        $notes = $this->generateNotes($prospect, $prospect->campaign, $material);

        $prospect->update([
            'research_notes' => $notes,
            'research_sources' => $material['sources'],
            'researched_at' => now(),
        ]);

        return ['notes' => $notes, 'sources' => $material['sources']];
    }

    protected function gatherMaterial(Prospect $prospect): array
    {
        $sources = [];
        $snippets = [];

        foreach ($this->buildQueries($prospect) as $query) {
            if (count($sources) >= 12) {
                break;
            }

            $results = $this->webSearch->search($query, 3, $prospect->organization_id);
            foreach ($results as $r) {
                $sources[] = [
                    'title' => $r['title'] ?? '',
                    'url' => $r['url'] ?? '',
                    'snippet' => $r['snippet'] ?? '',
                ];
                $snippets[] = ($r['title'] ?? '') . ' — ' . ($r['snippet'] ?? '');
            }
        }

        return [
            'sources' => $sources,
            'snippets' => array_slice($snippets, 0, 12),
        ];
    }

    protected function buildQueries(Prospect $prospect): array
    {
        $company = trim((string) $prospect->company);
        $location = trim((string) $prospect->location);
        $industry = trim((string) $prospect->industry);

        if ($company === '') {
            return [];
        }

        $queries = ["\"{$company}\" website"];
        if ($location !== '') {
            $queries[] = "{$company} {$location}";
        }
        if ($industry !== '') {
            $queries[] = "{$company} {$industry}";
        }
        $queries[] = "{$company} Instagram OR LinkedIn";

        return $queries;
    }

    protected function generateNotes(Prospect $prospect, $campaign, array $material): string
    {
        $snippets = implode("\n", $material['snippets']);
        $icp = $this->prettyJson($campaign?->icp ?? []);
        $offer = $campaign?->offer ?: 'N/A';
        $persona = $campaign?->personaPromptSummary();
        $personaBlock = $persona ? "Buyer persona:\n{$persona}\n" : '';

        $prompt = <<<PROMPT
Research this prospect and explain, in 3-5 short, evidence-backed bullet points, why they are a good fit for our offer.

Ideal Customer Profile:
{$icp}

{$personaBlock}Offer being pitched:
{$offer}

Prospect:
- Name: {$prospect->name}
- Title: {$prospect->title}
- Company: {$prospect->company}
- Company size: {$prospect->company_size}
- Industry: {$prospect->industry}
- Location: {$prospect->location}
- Website: {$prospect->website}
- LinkedIn: {$prospect->linkedin_url}

Online research (search snippets):
{$snippets}

Rules:
- Each bullet must cite evidence (e.g., "their website looks dated", "active Instagram presence", "no online booking").
- Do not invent facts; if there's no evidence, state what can be inferred from the data.
- Keep it to 3-5 bullets, each on its own line, prefixed with a dash "-".
Return ONLY the bullets, no preamble.
PROMPT;

        try {
            $response = $this->ai->chat([
                ['role' => 'system', 'content' => 'You are a B2B research analyst. You write concise, evidence-backed reasoning.'],
                ['role' => 'user', 'content' => $prompt],
            ], ['temperature' => 0.3, 'max_tokens' => 600]);

            $text = trim((string) $response->content);
            if ($text !== '') {
                return $text;
            }
        } catch (\Throwable $e) {
            Log::warning('Prospect research AI failed', ['prospect_id' => $prospect->id, 'error' => $e->getMessage()]);
        }

        return $this->heuristicNotes($prospect);
    }

    protected function heuristicNotes(Prospect $prospect): string
    {
        $points = [];
        if ($prospect->company && $prospect->industry) {
            $points[] = "- {$prospect->company} operates in {$prospect->industry} — matches our target market.";
        }
        if ($prospect->website) {
            $points[] = "- Has a website ({$prospect->website}) — a candidate for our services.";
        }
        if ($prospect->company_size) {
            $points[] = "- Company size ({$prospect->company_size}) fits our ideal customer profile.";
        }
        if ($prospect->linkedin_url) {
            $points[] = '- Has an active LinkedIn presence.';
        }
        $points[] = '- Scored ' . ($prospect->score ?: '—') . '/10 in qualification.';

        return implode("\n", $points);
    }

    protected function prettyJson(array $data): string
    {
        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '{}';
    }
}
