<?php

namespace App\Ai\Tools\BuiltIn;

use App\Ai\Tools\BaseTool;
use App\Models\Prospect;
use App\Services\Prospecting\ProspectResearcherService;

class ResearchProspectTool extends BaseTool
{
    protected string $identifier = 'research_prospect';
    protected string $name = 'Research Prospect';
    protected string $description = 'Research a prospect online and generate evidence-backed reasons they are a good fit for the campaign ICP.';
    protected string $category = 'prospecting';

    public function getInputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'prospect_id' => ['type' => 'integer', 'description' => 'Prospect ID'],
            ],
            'required' => ['prospect_id'],
        ];
    }

    public function execute(array $parameters): array
    {
        $orgId = app('current_organization_id');
        if (! $orgId) {
            return $this->error('No tenant context available.');
        }

        $prospect = Prospect::where('organization_id', $orgId)->find($parameters['prospect_id'] ?? null);
        if (! $prospect) {
            return $this->error('Prospect not found.');
        }

        $result = app(ProspectResearcherService::class)->research($prospect);

        return $this->success('Research complete.', [
            'notes' => $result['notes'],
            'sources' => $result['sources'],
        ]);
    }
}
