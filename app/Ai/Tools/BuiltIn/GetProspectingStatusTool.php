<?php

namespace App\Ai\Tools\BuiltIn;

use App\Ai\Tools\BaseTool;
use App\Models\Prospect;
use App\Models\ProspectingCampaign;

class GetProspectingStatusTool extends BaseTool
{
    protected string $identifier = 'get_prospecting_status';
    protected string $name = 'Get Prospecting Status';
    protected string $description = 'Report the number of prospects by status for the current outbound campaigns.';
    protected string $category = 'prospecting';

    public function getInputSchema(): array
    {
        return ['type' => 'object', 'properties' => []];
    }

    public function execute(array $parameters): array
    {
        $orgId = app('current_organization_id');

        $campaigns = ProspectingCampaign::where('organization_id', $orgId)->get();

        $stats = [
            'campaigns' => $campaigns->count(),
            'found' => Prospect::where('organization_id', $orgId)->count(),
            'qualified' => Prospect::where('organization_id', $orgId)->where('status', 'qualified')->count(),
            'contacted' => Prospect::where('organization_id', $orgId)->whereNotNull('contacted_at')->count(),
            'replied' => Prospect::where('organization_id', $orgId)->whereNotNull('replied_at')->count(),
            'converted' => Prospect::where('organization_id', $orgId)->where('status', 'converted')->count(),
        ];

        $byCampaign = $campaigns->map(function ($campaign) {
            return [
                'campaign' => $campaign->name,
                'found' => $campaign->prospects()->count(),
                'qualified' => $campaign->prospects()->where('status', 'qualified')->count(),
                'contacted' => $campaign->prospects()->whereNotNull('contacted_at')->count(),
                'replied' => $campaign->prospects()->whereNotNull('replied_at')->count(),
                'converted' => $campaign->prospects()->where('status', 'converted')->count(),
            ];
        })->values()->toArray();

        $summary = sprintf(
            '%d prospect(s) found, %d qualified, %d contacted, %d replied, %d converted.',
            $stats['found'],
            $stats['qualified'],
            $stats['contacted'],
            $stats['replied'],
            $stats['converted'],
        );

        return $this->success($summary, [
            'summary' => $summary,
            'stats' => $stats,
            'campaigns' => $byCampaign,
        ]);
    }
}
