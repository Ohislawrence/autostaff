<?php

namespace App\Ai\Tools\BuiltIn;

use App\Ai\Tools\BaseTool;
use App\Models\Prospect;
use App\Services\Prospecting\OutreachService;

class DraftOutreachTool extends BaseTool
{
    protected string $identifier = 'draft_outreach';
    protected string $name = 'Draft Outreach Email';
    protected string $description = 'Write a hyper-personalized 2-pass AI outreach email for a prospect.';
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

        $email = app(OutreachService::class)->generate($prospect);

        return $this->success('Email drafted.', ['subject' => $email['subject'], 'body' => $email['body']]);
    }

    protected function find(?string $identifier): ?Prospect
    {
        $orgId = app('current_organization_id');

        return Prospect::where('organization_id', $orgId)
            ->where(fn ($q) => $q->where('id', $identifier)->orWhere('email', $identifier))
            ->first();
    }
}
