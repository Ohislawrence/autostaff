<?php

namespace App\Automation\Triggers;

use App\Automation\Contracts\TriggerInterface;
use App\Models\Lead;

class LeadStageChangedTrigger implements TriggerInterface
{
    public function identifier(): string
    {
        return 'lead_stage_changed';
    }

    public function label(): string
    {
        return 'Lead Stage Changed';
    }

    public function description(): string
    {
        return 'Fires when a lead moves from one pipeline stage to another.';
    }

    public function configSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'from_stage' => [
                    'type' => 'string',
                    'description' => 'Only fire if moved FROM this stage',
                    'enum' => ['new', 'contacted', 'qualified', 'proposal', 'won', 'lost'],
                ],
                'to_stage' => [
                    'type' => 'string',
                    'description' => 'Only fire if moved TO this stage',
                    'enum' => ['new', 'contacted', 'qualified', 'proposal', 'won', 'lost'],
                ],
            ],
        ];
    }

    public function extractPayload(object|array $event): array
    {
        /** @var Lead $lead */
        $lead = $event['lead'];
        $fromStage = $event['from_stage'];
        $toStage = $event['to_stage'];

        return [
            'lead_id' => $lead->id,
            'customer_id' => $lead->customer_id,
            'customer_name' => $lead->customer?->first_name . ' ' . $lead->customer?->last_name,
            'lead_score' => $lead->score,
            'lead_stage' => $toStage,
            'from_stage' => $fromStage,
            'to_stage' => $toStage,
            'product_interest' => $lead->product_interest,
            'estimated_value' => $lead->estimated_value,
        ];
    }

    public function extractOrganizationId(object|array $event): int
    {
        return $event['lead']->organization_id;
    }
}