<?php

namespace App\Automation\Triggers;

use App\Automation\Contracts\TriggerInterface;
use App\Models\Lead;

class NewLeadTrigger implements TriggerInterface
{
    public function identifier(): string
    {
        return 'new_lead';
    }

    public function label(): string
    {
        return 'New Lead Created';
    }

    public function description(): string
    {
        return 'Fires when a new lead is generated from a conversation or form submission.';
    }

    public function configSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'min_score' => [
                    'type' => 'number',
                    'description' => 'Only fire for leads with score >= this value',
                ],
                'stages' => [
                    'type' => 'array',
                    'description' => 'Only fire for specific stages',
                    'items' => ['type' => 'string', 'enum' => ['new', 'contacted', 'qualified', 'proposal', 'won', 'lost']],
                ],
            ],
        ];
    }

    public function extractPayload(object|array $event): array
    {
        /** @var Lead $lead */
        $lead = $event['lead'];

        return [
            'lead_id' => $lead->id,
            'customer_id' => $lead->customer_id,
            'customer_name' => $lead->customer?->first_name . ' ' . $lead->customer?->last_name,
            'lead_score' => $lead->score,
            'lead_stage' => $lead->stage,
            'product_interest' => $lead->product_interest,
            'estimated_value' => $lead->estimated_value,
            'source' => $lead->source,
        ];
    }

    public function extractOrganizationId(object|array $event): int
    {
        return $event['lead']->organization_id;
    }
}