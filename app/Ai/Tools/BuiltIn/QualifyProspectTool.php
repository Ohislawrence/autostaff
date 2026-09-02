<?php

namespace App\Ai\Tools\BuiltIn;

use App\Ai\Tools\BaseTool;
use App\Models\Prospect;
use App\Services\Prospecting\ProspectQualifierService;

class QualifyProspectTool extends BaseTool
{
    protected string $identifier = 'qualify_prospect';
    protected string $name = 'Qualify Prospect';
    protected string $description = 'Score a prospect 1-10 against its campaign ICP.';
    protected string $category = 'prospecting';

    public function getInputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'identifier' => ['type' => 'string', 'description' => 'Prospect email or ID'],
            ],
            'required' => ['identifier'],
        ];
    }

    public function execute(array $parameters): array
    {
        $prospect = $this->find($parameters['identifier'] ?? null);
        if (! $prospect) {
            return $this->error('Prospect not found.');
        }

        $result = app(ProspectQualifierService::class)->qualify($prospect);

        return $this->success("Prospect scored {$result['score']}/10 ({$result['status']}).", $result);
    }

    protected function find(?string $identifier): ?Prospect
    {
        $orgId = app('current_organization_id');

        return Prospect::where('organization_id', $orgId)
            ->where(fn ($q) => $q->where('id', $identifier)->orWhere('email', $identifier))
            ->first();
    }
}
