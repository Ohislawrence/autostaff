<?php

namespace App\Ai\Tools\BuiltIn;

use App\Ai\Tools\BaseTool;
use App\Models\Prospect;
use App\Services\Prospecting\OutreachService;

class SendOutreachTool extends BaseTool
{
    protected string $identifier = 'send_outreach';
    protected string $name = 'Send Outreach Email';
    protected string $description = 'Send the drafted outreach email to a prospect (runs compliance checks).';
    protected string $category = 'prospecting';
    protected bool $requiresConfirmation = true;

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

        $result = app(OutreachService::class)->send($prospect);

        if (empty($result['sent'])) {
            return $this->error($result['error'] ?? 'Could not send email.');
        }

        return $this->success('Email sent.', ['subject' => $result['subject']]);
    }

    protected function find(?string $identifier): ?Prospect
    {
        $orgId = app('current_organization_id');

        return Prospect::where('organization_id', $orgId)
            ->where(fn ($q) => $q->where('id', $identifier)->orWhere('email', $identifier))
            ->first();
    }
}
