<?php

namespace App\Ai\Tools\BuiltIn;

use App\Ai\Tools\BaseTool;
use App\Models\Customer;
use App\Models\Organization;
use App\Services\Prospecting\ProspectConversionService;

class GenerateProposalTool extends BaseTool
{
    protected string $identifier = 'generate_proposal';
    protected string $name = 'Generate Proposal';
    protected string $description = 'Create a service proposal (quotation + invoice) for a customer at a specific price — for services not in a product catalog.';
    protected string $category = 'sales';
    protected bool $requiresConfirmation = true;

    public function getInputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'customer_id' => ['type' => 'string', 'description' => 'Customer UUID or ID'],
                'amount' => ['type' => 'number', 'description' => 'Proposal amount'],
                'description' => ['type' => 'string', 'description' => 'What is being proposed (e.g. "Website redesign")'],
                'notes' => ['type' => 'string'],
            ],
            'required' => ['customer_id', 'amount'],
        ];
    }

    public function getOutputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'success' => ['type' => 'boolean'],
                'quotation_id' => ['type' => 'integer'],
                'invoice_id' => ['type' => 'integer'],
                'total' => ['type' => 'string'],
            ],
        ];
    }

    public function execute(array $parameters): array
    {
        $orgId = app('current_organization_id');
        $organization = Organization::find($orgId);
        if (! $organization) {
            return $this->error('No tenant context available.');
        }

        $customer = Customer::where('organization_id', $orgId)
            ->where(fn ($q) => $q->where('uuid', $parameters['customer_id'])->orWhere('id', $parameters['customer_id']))
            ->first();
        if (! $customer) {
            return $this->error('Customer not found.');
        }

        $amount = (float) ($parameters['amount'] ?? 0);
        if ($amount <= 0) {
            return $this->error('Amount must be greater than zero.');
        }

        $description = trim((string) ($parameters['description'] ?? 'Service')) ?: 'Service';

        $result = app(ProspectConversionService::class)->proposal($organization, $customer, $amount, $description);

        return $this->success("Proposal generated ({$organization->currency} {$amount}).", [
            'quotation_id' => $result['quotation_id'],
            'invoice_id' => $result['invoice_id'],
            'total' => (string) $amount,
        ]);
    }
}
