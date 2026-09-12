<?php

namespace App\Ai\Tools\BuiltIn;

use App\Ai\Tools\BaseTool;
use App\Jobs\Prospecting\ResearchCampaignJob;
use App\Jobs\Prospecting\RunHuntJob;
use App\Jobs\Prospecting\RunOutreachJob;
use App\Models\ProspectingCampaign;

class RunOutboundPipelineTool extends BaseTool
{
    protected string $identifier = 'run_outbound_pipeline';
    protected string $name = 'Run Outbound Pipeline';
    protected string $description = 'Run the full outbound pipeline for a campaign in one go: hunt, qualify, research, and outreach.';
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
                'budget' => ['type' => 'string'],
                'offer' => ['type' => 'string', 'description' => 'What is being pitched'],
                'daily_limit' => ['type' => 'integer'],
                'include_outreach' => ['type' => 'boolean', 'description' => 'Whether to also send outreach emails (default true)'],
            ],
        ];
    }

    public function execute(array $parameters): array
    {
        $orgId = app('current_organization_id');
        if (! $orgId) {
            return $this->error('No tenant context available.');
        }

        // Attribute the campaign to the AI employee that triggered the run.
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
                    'budget' => $parameters['budget'] ?? null,
                ],
                'offer' => $parameters['offer'] ?? null,
                'tone' => 'professional',
                'daily_limit' => (int) ($parameters['daily_limit'] ?? 25),
                'auto_outreach' => true,
                'status' => 'active',
            ]);
        } elseif ($employeeId && ! $campaign->ai_employee_id) {
            $campaign->update(['ai_employee_id' => $employeeId]);
        }

        $includeOutreach = (bool) ($parameters['include_outreach'] ?? true);

        // RunHuntJob already hunts + qualifies, so chain research → outreach after it.
        $chain = [new ResearchCampaignJob($campaign->id)];
        if ($includeOutreach) {
            $chain[] = new RunOutreachJob($campaign->id);
        }

        RunHuntJob::withChain($chain)->dispatch($campaign->id);

        return $this->success(
            $includeOutreach
                ? "Running the full outbound pipeline for \"{$name}\": hunt → qualify → research → outreach."
                : "Running hunt → qualify → research for \"{$name}\" (outreach skipped).",
            ['campaign_id' => $campaign->id]
        );
    }
}
