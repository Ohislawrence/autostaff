<?php

namespace App\Ai\Tools\BuiltIn;

use App\Ai\Tools\BaseTool;
use App\Models\ProspectingCampaign;
use App\Services\Prospecting\ProspectHunterService;
use App\Services\Prospecting\ProspectQualifierService;

class HuntIcpTool extends BaseTool
{
    protected string $identifier = 'hunt_icp';
    protected string $name = 'Hunt Ideal Customer Profile';
    protected string $description = 'Search the web and generate ICP-matching prospects for an outbound campaign, then qualify them 1-10.';
    protected string $category = 'prospecting';

    public function getInputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'campaign_name' => ['type' => 'string', 'description' => 'Campaign name (defaults to "SDR Outreach")'],
                'industry' => ['type' => 'array', 'items' => ['type' => 'string']],
                'company_size' => ['type' => 'string'],
                'geography' => ['type' => 'array', 'items' => ['type' => 'string']],
                'job_titles' => ['type' => 'array', 'items' => ['type' => 'string']],
                'keywords' => ['type' => 'array', 'items' => ['type' => 'string']],
                'exclusions' => ['type' => 'array', 'items' => ['type' => 'string']],
                'budget' => ['type' => 'string'],
                'pain_points' => ['type' => 'string'],
                'offer' => ['type' => 'string', 'description' => 'What is being pitched'],
                'daily_limit' => ['type' => 'integer'],
            ],
        ];
    }

    public function execute(array $parameters): array
    {
        $orgId = app('current_organization_id');
        if (! $orgId) {
            return $this->error('No tenant context available.');
        }

        // Attribute the campaign to the AI employee that triggered this hunt
        // (ToolExecutor injects the employee via `_employee`).
        $employeeId = $parameters['_employee']?->id ?? null;

        $name = $parameters['campaign_name'] ?? 'SDR Outreach';
        $campaign = ProspectingCampaign::where('organization_id', $orgId)->where('name', $name)->first();

        if (! $campaign) {
            $campaign = ProspectingCampaign::create([
                'organization_id' => $orgId,
                'ai_employee_id' => $employeeId,
                'name' => $name,
                'icp' => [
                    'industry' => $parameters['industry'] ?? [],
                    'company_size' => $parameters['company_size'] ?? null,
                    'geography' => $parameters['geography'] ?? [],
                    'job_titles' => $parameters['job_titles'] ?? [],
                    'keywords' => $parameters['keywords'] ?? [],
                    'exclusions' => $parameters['exclusions'] ?? [],
                    'budget' => $parameters['budget'] ?? null,
                    'pain_points' => $parameters['pain_points'] ?? null,
                ],
                'offer' => $parameters['offer'] ?? null,
                'tone' => 'professional',
                'daily_limit' => (int) ($parameters['daily_limit'] ?? 25),
                'compliance_regions' => ['us', 'uk'],
                'status' => 'active',
            ]);
        } elseif ($employeeId && ! $campaign->ai_employee_id) {
            // Backfill attribution for a campaign created before the link existed.
            $campaign->update(['ai_employee_id' => $employeeId]);
        }

        $result = app(ProspectHunterService::class)->hunt($campaign);

        $qualifier = app(ProspectQualifierService::class);
        $qualified = 0;
        $campaign->prospects()->where('status', 'new')->get()->each(function ($p) use ($qualifier, &$qualified) {
            try {
                $qualifier->qualify($p);
                $qualified++;
            } catch (\Throwable $e) {
                // keep going
            }
        });

        return $this->success("Hunted {$result['created']} prospects and qualified {$qualified}.", [
            'campaign_id' => $campaign->id,
            'created' => $result['created'],
            'qualified' => $qualified,
            'sources' => $result['sources'],
        ]);
    }
}
