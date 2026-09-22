<?php

namespace App\Services\Prospecting;

use App\Ai\Providers\AiProviderInterface;
use App\Models\BuyerPersona;
use App\Models\Organization;
use App\Services\Ai\AiUsageRecorder;
use App\Services\Guardrails\CostGuardService;
use App\Support\AiJson;
use Illuminate\Support\Facades\Log;

class BuyerPersonaService
{
    public function __construct(
        protected AiProviderInterface $ai,
        protected ProspectingSettingsService $settings,
        protected AiUsageRecorder $recorder,
        protected CostGuardService $costGuard,
    ) {}

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'avatar' => 'nullable|string|max:10',
            'role_titles' => 'nullable|string',
            'demographics' => 'nullable|string',
            'goals' => 'nullable|string',
            'pains' => 'nullable|string',
            'objections' => 'nullable|string',
            'buying_triggers' => 'nullable|string',
            'messaging_hooks' => 'nullable|string',
            'value_props' => 'nullable|string',
            'channels' => 'nullable|string',
            'current_solution' => 'nullable|string',
            'keywords' => 'nullable|string',
        ];
    }

    public function create(array $input, Organization $organization): BuyerPersona
    {
        $persona = new BuyerPersona();
        $persona->organization_id = $organization->id;
        $this->apply($persona, $input);
        $persona->save();

        return $persona;
    }

    public function update(BuyerPersona $persona, array $input): BuyerPersona
    {
        $this->apply($persona, $input);
        $persona->save();

        return $persona;
    }

    /**
     * Generate a full persona draft from a value proposition using DeepSeek.
     */
    public function generate(array $input, Organization $organization): BuyerPersona
    {
        $offer = trim((string) ($input['offer'] ?? ''));
        $industry = trim((string) ($input['industry'] ?? ''));

        $prompt = <<<PROMPT
You are a B2B outbound strategist. Build ONE concrete buyer persona for this offer.

Offer / value proposition:
{$offer}

Context (industry / market):
{$industry}

Return ONLY valid JSON in exactly this shape:
{
  "name": "short persona name (e.g. 'Dental Clinic Owner')",
  "avatar": "a single emoji",
  "role_titles": ["2-4 job titles"],
  "demographics": ["2-4 descriptors: seniority, company size, context"],
  "goals": ["3-5 goals they care about"],
  "pains": ["3-5 concrete pain points"],
  "objections": ["3-5 objections they raise"],
  "buying_triggers": ["3-5 events that make them ready to buy"],
  "messaging_hooks": ["3-5 lines or angles that resonate"],
  "value_props": ["3-5 benefits to lead with"],
  "channels": ["2-4 places they spend time online"],
  "current_solution": "what they use today / status quo",
  "keywords": ["4-8 search keywords to find them"]
}
PROMPT;

        $options = ['temperature' => 0.7, 'max_tokens' => 1500];
        if ($model = $this->settings->deepseekModel()) {
            $options['model'] = $model;
        }

        $data = null;
        if ($this->costGuard->checkBudget($organization->id)) {
            try {
                $response = $this->ai->chat([
                    ['role' => 'system', 'content' => 'You return strict JSON only.'],
                    ['role' => 'user', 'content' => $prompt],
                ], $options);

                $this->recorder->record($organization->id, $response, [
                    'system_prompt' => 'You return strict JSON only.',
                    'user_prompt' => $prompt,
                ]);

                $data = AiJson::parse($response->content);
            } catch (\Throwable $e) {
                Log::warning('Buyer persona AI generation failed', ['error' => $e->getMessage()]);
            }
        }

        if (! is_array($data) || empty($data['name'])) {
            $data = [
                'name' => $offer !== '' ? 'Buyer for: ' . $offer : 'New persona',
                'avatar' => '🧑‍💼',
                'role_titles' => ['Decision maker'],
                'goals' => [],
                'pains' => [],
                'objections' => [],
                'buying_triggers' => [],
                'messaging_hooks' => [],
                'value_props' => [],
                'channels' => [],
                'current_solution' => null,
                'keywords' => [],
            ];
        }

        return $this->create([
            'name' => trim((string) ($data['name'] ?? 'New persona')),
            'avatar' => trim((string) ($data['avatar'] ?? '🧑‍💼')),
            'role_titles' => $this->joinList($data['role_titles'] ?? []),
            'demographics' => $this->joinList($data['demographics'] ?? []),
            'goals' => $this->joinList($data['goals'] ?? []),
            'pains' => $this->joinList($data['pains'] ?? []),
            'objections' => $this->joinList($data['objections'] ?? []),
            'buying_triggers' => $this->joinList($data['buying_triggers'] ?? []),
            'messaging_hooks' => $this->joinList($data['messaging_hooks'] ?? []),
            'value_props' => $this->joinList($data['value_props'] ?? []),
            'channels' => $this->joinList($data['channels'] ?? []),
            'current_solution' => $data['current_solution'] ?? null,
            'keywords' => $this->joinList($data['keywords'] ?? []),
        ], $organization);
    }

    protected function apply(BuyerPersona $persona, array $input): void
    {
        $persona->name = $input['name'];
        $persona->avatar = $input['avatar'] ?? null;
        $persona->role_titles = $this->split($input['role_titles'] ?? null);
        $persona->demographics = $this->split($input['demographics'] ?? null);
        $persona->goals = $this->split($input['goals'] ?? null);
        $persona->pains = $this->split($input['pains'] ?? null);
        $persona->objections = $this->split($input['objections'] ?? null);
        $persona->buying_triggers = $this->split($input['buying_triggers'] ?? null);
        $persona->messaging_hooks = $this->split($input['messaging_hooks'] ?? null);
        $persona->value_props = $this->split($input['value_props'] ?? null);
        $persona->channels = $this->split($input['channels'] ?? null);
        $persona->current_solution = $input['current_solution'] ?? null;
        $persona->keywords = $this->split($input['keywords'] ?? null);
    }

    protected function split(mixed $value): array
    {
        if (is_array($value)) {
            return array_values(array_filter(array_map('trim', $value)));
        }

        if (! $value) {
            return [];
        }

        return array_values(array_filter(array_map('trim', preg_split('/[\n,]+/', (string) $value))));
    }

    protected function joinList(mixed $value): string
    {
        return implode(', ', $this->split($value));
    }
}

