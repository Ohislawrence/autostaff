<?php

namespace App\Services\Prospecting;

use App\Models\AiEmployee;
use App\Models\BuyerPersona;
use App\Models\Organization;
use App\Models\ProspectingCampaign;

/**
 * Single source of truth for creating/updating prospecting campaigns,
 * shared by the standalone Prospecting form and the AI-Employee wizard.
 */
class CampaignCreator
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'offer' => 'nullable|string',
            'tone' => 'nullable|string|in:professional,friendly,persuasive,concise',
            'sender_name' => 'nullable|string|max:255',
            'sender_email' => 'nullable|email|max:255',
            'daily_limit' => 'nullable|integer|min:1|max:500',
            'auto_outreach' => 'boolean',
            'buyer_persona_id' => 'nullable|integer',
            'icp_industry' => 'nullable|string',
            'icp_company_size' => 'nullable|string',
            'icp_geography' => 'nullable|string',
            'icp_job_titles' => 'nullable|string',
            'icp_keywords' => 'nullable|string',
            'icp_exclusions' => 'nullable|string',
            'icp_budget' => 'nullable|string',
            'icp_pain_points' => 'nullable|string',
            'postal_address' => 'nullable|string',
            'from_domain' => 'nullable|string|max:255',
            'compliance_regions' => 'nullable|string',
            'blocked_sources' => 'nullable|string',
            'blocked_regions' => 'nullable|string',
            'allow_ai_generated' => 'boolean',
            'max_per_hour' => 'nullable|integer|min:1|max:1000',
            'require_approval_ai_contacts' => 'boolean',
        ];
    }

    public function create(array $input, Organization $organization, ?AiEmployee $employee = null): ProspectingCampaign
    {
        $campaign = new ProspectingCampaign();
        $campaign->organization_id = $organization->id;
        $campaign->ai_employee_id = $employee?->id;
        $campaign->status = 'draft';

        $this->apply($campaign, $input);
        $campaign->save();

        return $campaign;
    }

    public function update(ProspectingCampaign $campaign, array $input): ProspectingCampaign
    {
        $this->apply($campaign, $input);
        $campaign->save();

        return $campaign;
    }

    protected function apply(ProspectingCampaign $campaign, array $input): void
    {
        $campaign->name = $input['name'];
        $campaign->description = $input['description'] ?? null;
        $campaign->offer = $input['offer'] ?? null;
        $campaign->tone = $input['tone'] ?? 'professional';
        $campaign->sender_name = $input['sender_name'] ?? null;
        $campaign->sender_email = $input['sender_email'] ?? null;
        $campaign->daily_limit = (int) ($input['daily_limit'] ?? 25);
        $campaign->auto_outreach = (bool) ($input['auto_outreach'] ?? false);
        $campaign->icp = $this->buildIcp($input);
        $this->applyBuyerPersona($campaign, $input['buyer_persona_id'] ?? null);
        $campaign->postal_address = $input['postal_address'] ?? null;
        $campaign->from_domain = $input['from_domain'] ?? null;
        $campaign->compliance_regions = $this->split($input['compliance_regions'] ?? ['us', 'uk']);
        $campaign->sourcing_rules = [
            'blocked_sources' => $this->split($input['blocked_sources'] ?? null),
            'blocked_regions' => $this->split($input['blocked_regions'] ?? null),
            'allow_ai_generated' => (bool) ($input['allow_ai_generated'] ?? true),
        ];
        $campaign->max_per_hour = (int) ($input['max_per_hour'] ?? 50);
        $campaign->require_approval_ai_contacts = (bool) ($input['require_approval_ai_contacts'] ?? false);
    }

    protected function buildIcp(array $input): array
    {
        return [
            'industry' => $this->split($input['icp_industry'] ?? null),
            'company_size' => $input['icp_company_size'] ?? null,
            'geography' => $this->split($input['icp_geography'] ?? null),
            'job_titles' => $this->split($input['icp_job_titles'] ?? null),
            'keywords' => $this->split($input['icp_keywords'] ?? null),
            'exclusions' => $this->split($input['icp_exclusions'] ?? null),
            'budget' => $input['icp_budget'] ?? null,
            'pain_points' => $input['icp_pain_points'] ?? null,
        ];
    }

    protected function applyBuyerPersona(ProspectingCampaign $campaign, mixed $personaId): void
    {
        $persona = $personaId
            ? BuyerPersona::where('id', $personaId)
                ->where('organization_id', $campaign->organization_id)
                ->first()
            : null;

        $campaign->buyer_persona_id = $persona?->id;

        if ($persona) {
            $campaign->buyer_persona_snapshot = collect($persona->toArray())
                ->only([
                    'name', 'avatar', 'role_titles', 'demographics', 'goals', 'pains',
                    'objections', 'buying_triggers', 'messaging_hooks', 'value_props',
                    'channels', 'current_solution', 'keywords',
                ])
                ->all();
        } else {
            $campaign->buyer_persona_snapshot = null;
        }
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
}
