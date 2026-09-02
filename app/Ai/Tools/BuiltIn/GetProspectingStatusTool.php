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

        $stats = [
            'campaigns' => ProspectingCampaign::where('organization_id', $orgId)->count(),
            'prospects' => Prospect::where('organization_id', $orgId)->count(),
            'qualified' => Prospect::where('organization_id', $orgId)->where('status', 'qualified')->count(),
            'contacted' => Prospect::where('organization_id', $orgId)->where('status', 'contacted')->count(),
            'replied' => Prospect::where('organization_id', $orgId)->where('status', 'replied')->count(),
        ];

        return $this->success('Prospecting status retrieved.', $stats);
    }
}
