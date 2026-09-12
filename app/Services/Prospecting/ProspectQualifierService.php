<?php

namespace App\Services\Prospecting;

use App\Ai\Providers\AiProviderInterface;
use App\Models\ProspectingSettings;
use App\Models\Prospect;
use App\Support\AiJson;
use Illuminate\Support\Facades\Log;

class ProspectQualifierService
{
    public function __construct(
        protected AiProviderInterface $ai,
        protected ProspectingSettingsService $settings,
    ) {}

    /**
     * Score a prospect 1-10 against its campaign ICP using DeepSeek.
     */
    public function qualify(Prospect $prospect): array
    {
        $campaign = $prospect->campaign;
        $threshold = (int) (ProspectingSettings::instance()->qualification_threshold ?: 7);

        $result = $this->scoreWithAi($prospect, $campaign);

        if ($result === null) {
            $result = $this->heuristicScore($prospect);
        }

        $score = min(10, max(1, (int) ($result['score'] ?? 5)));
        $status = $score >= $threshold ? 'qualified' : 'disqualified';

        $prospect->update([
            'score' => $score,
            'score_breakdown' => $result['breakdown'] ?? [],
            'qualification_notes' => $result['notes'] ?? null,
            'status' => $status,
        ]);

        return [
            'score' => $score,
            'status' => $status,
            'breakdown' => $result['breakdown'] ?? [],
            'notes' => $result['notes'] ?? null,
            'threshold' => $threshold,
        ];
    }

    protected function scoreWithAi(Prospect $prospect, $campaign): ?array
    {
        $offer = $campaign->offer ?: 'N/A';
        $persona = $campaign->personaPromptSummary();

        $personaBlock = $persona
            ? "Buyer persona:\n{$persona}\n"
            : '';

        $prompt = <<<PROMPT
Score this prospect (1-10) for fit against the Ideal Customer Profile.

Ideal Customer Profile:
{$this->prettyJson($campaign->icp ?? [])}

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

Return ONLY valid JSON in this exact shape:
{"score": 8, "breakdown": {"icp_fit": 8, "persona_fit": 8, "authority": 7, "reachability": 9, "intent_signal": 5}, "notes": "one concise sentence explaining the score"}
Use 1 = terrible fit, 10 = perfect ICP + persona match.
PROMPT;

        $options = [
            'temperature' => 0.2,
            'max_tokens' => 600,
        ];
        if ($model = $this->settings->deepseekModel()) {
            $options['model'] = $model;
        }

        $response = $this->ai->chat([
            ['role' => 'system', 'content' => 'You return strict JSON only.'],
            ['role' => 'user', 'content' => $prompt],
        ], $options);

        $data = AiJson::parse($response->content);

        return is_array($data) ? $data : null;
    }

    protected function heuristicScore(Prospect $prospect): array
    {
        $score = 3;
        $breakdown = ['baseline' => 3];

        if ($prospect->email) {
            $breakdown['has_email'] = 2;
            $score += 2;
        }
        if ($prospect->title) {
            $breakdown['has_title'] = 2;
            $score += 2;
        }
        if ($prospect->company) {
            $breakdown['has_company'] = 1;
            $score += 1;
        }
        if ($prospect->website) {
            $breakdown['has_website'] = 1;
            $score += 1;
        }
        if ($prospect->linkedin_url) {
            $breakdown['has_linkedin'] = 1;
            $score += 1;
        }

        return [
            'score' => min(10, $score),
            'breakdown' => $breakdown,
            'notes' => 'Heuristic fallback score (AI unavailable).',
        ];
    }

    protected function prettyJson(array $data): string
    {
        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '{}';
    }
}
