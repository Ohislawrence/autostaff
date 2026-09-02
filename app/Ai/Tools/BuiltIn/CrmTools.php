<?php

namespace App\Ai\Tools\BuiltIn;

use App\Ai\Tools\BaseTool;
use App\Models\Customer;
use App\Models\Lead;
use App\Services\Automation\AutomationService;

class GetProductTool extends BaseTool
{
    protected string $identifier = 'get_product';
    protected string $name = 'Get Product';
    protected string $description = 'Get detailed information about a specific product by ID.';
    protected string $category = 'products';

    public function getInputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'product_id' => ['type' => 'string', 'description' => 'The product UUID or ID'],
            ],
            'required' => ['product_id'],
        ];
    }

    public function getOutputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'success' => ['type' => 'boolean'],
                'product' => ['type' => 'object'],
            ],
        ];
    }

    public function execute(array $parameters): array
    {
        $orgId = app('current_organization_id');
        $product = \App\Models\Product::where('organization_id', $orgId)
            ->where(fn ($q) => $q->where('uuid', $parameters['product_id'])->orWhere('id', $parameters['product_id']))
            ->first();

        if (! $product) {
            return $this->error('Product not found.');
        }

        return $this->success('Product found.', [
            'product' => [
                'id' => $product->id,
                'uuid' => $product->uuid,
                'name' => $product->name,
                'description' => $product->description,
                'price' => (string) $product->price,
                'sale_price' => $product->sale_price ? (string) $product->sale_price : null,
                'currency' => $product->currency,
                'category' => $product->category,
                'sku' => $product->sku,
                'is_active' => $product->is_active,
            ],
        ]);
    }
}

class GetPriceTool extends BaseTool
{
    protected string $identifier = 'get_price';
    protected string $name = 'Get Price';
    protected string $description = 'Get the current price of a product.';
    protected string $category = 'products';

    public function getInputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'product_id' => ['type' => 'string', 'description' => 'Product UUID or ID'],
            ],
            'required' => ['product_id'],
        ];
    }

    public function getOutputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'success' => ['type' => 'boolean'],
                'product_name' => ['type' => 'string'],
                'price' => ['type' => 'string'],
                'sale_price' => ['type' => 'string'],
                'currency' => ['type' => 'string'],
            ],
        ];
    }

    public function execute(array $parameters): array
    {
        $orgId = app('current_organization_id');
        $product = \App\Models\Product::where('organization_id', $orgId)
            ->where(fn ($q) => $q->where('uuid', $parameters['product_id'])->orWhere('id', $parameters['product_id']))
            ->first();

        if (! $product) return $this->error('Product not found.');

        $displayPrice = $product->sale_price ?: $product->price;

        return $this->success("The price of {$product->name} is {$displayPrice} {$product->currency}.", [
            'product_name' => $product->name,
            'price' => (string) $product->price,
            'sale_price' => $product->sale_price ? (string) $product->sale_price : null,
            'display_price' => (string) $displayPrice,
            'currency' => $product->currency,
        ]);
    }
}

class CheckInventoryTool extends BaseTool
{
    protected string $identifier = 'check_inventory';
    protected string $name = 'Check Inventory';
    protected string $description = 'Check the current inventory level for a product.';
    protected string $category = 'products';

    public function getInputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'product_id' => ['type' => 'string', 'description' => 'Product UUID or ID'],
            ],
            'required' => ['product_id'],
        ];
    }

    public function getOutputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'success' => ['type' => 'boolean'],
                'product_name' => ['type' => 'string'],
                'quantity' => ['type' => 'integer'],
                'in_stock' => ['type' => 'boolean'],
            ],
        ];
    }

    public function execute(array $parameters): array
    {
        $orgId = app('current_organization_id');
        $product = \App\Models\Product::where('organization_id', $orgId)
            ->where(fn ($q) => $q->where('uuid', $parameters['product_id'])->orWhere('id', $parameters['product_id']))
            ->first();

        if (! $product) return $this->error('Product not found.');

        $inventory = $product->inventory()->first();
        $quantity = $inventory ? $inventory->quantity : 0;

        return $this->success($quantity > 0 ? "{$product->name} is in stock ({$quantity} available)." : "{$product->name} is out of stock.", [
            'product_name' => $product->name,
            'quantity' => $quantity,
            'in_stock' => $quantity > 0,
        ]);
    }
}

class CreateLeadTool extends BaseTool
{
    protected string $identifier = 'create_lead';
    protected string $name = 'Create Lead';
    protected string $description = 'Create a new lead for a customer showing interest in a product or service.';
    protected string $category = 'leads';

    public function getInputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'customer_id' => ['type' => 'string', 'description' => 'Customer UUID or ID'],
                'product_interest' => ['type' => 'string', 'description' => 'Product or service the customer is interested in'],
                'notes' => ['type' => 'string', 'description' => 'Additional notes about the lead'],
                'source' => ['type' => 'string', 'description' => 'Lead source', 'default' => 'ai'],
            ],
            'required' => ['customer_id'],
        ];
    }

    public function getOutputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'success' => ['type' => 'boolean'],
                'lead_id' => ['type' => 'integer'],
                'lead_uuid' => ['type' => 'string'],
                'stage' => ['type' => 'string'],
            ],
        ];
    }

    public function execute(array $parameters): array
    {
        $orgId = app('current_organization_id');

        $customer = Customer::where('organization_id', $orgId)
            ->where(fn ($q) => $q->where('uuid', $parameters['customer_id'])->orWhere('id', $parameters['customer_id']))
            ->first();

        if (! $customer) return $this->error('Customer not found.');

        $lead = Lead::create([
            'organization_id' => $orgId,
            'customer_id' => $customer->id,
            'stage' => 'new',
            'source' => $parameters['source'] ?? 'ai',
            'product_interest' => $parameters['product_interest'] ?? null,
            'notes' => $parameters['notes'] ?? null,
        ]);

        // Update customer lead stage
        $customer->update(['lead_stage' => 'new', 'last_contacted_at' => now()]);

        // Fire automation trigger for the newly created lead.
        try {
            app(AutomationService::class)->triggerOnLeadCreated($lead);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('Automation trigger failed in CreateLeadTool', [
                'error' => $e->getMessage(),
            ]);
        }

        return $this->success('Lead created successfully.', [
            'lead_id' => $lead->id,
            'lead_uuid' => $lead->uuid,
            'stage' => $lead->stage,
        ]);
    }
}

class CreateCustomerTool extends BaseTool
{
    protected string $identifier = 'create_customer';
    protected string $name = 'Create Customer';
    protected string $description = 'Create a new customer record in the CRM.';
    protected string $category = 'customers';

    public function getInputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'first_name' => ['type' => 'string', 'description' => 'First name'],
                'last_name' => ['type' => 'string', 'description' => 'Last name'],
                'email' => ['type' => 'string', 'description' => 'Email address'],
                'phone' => ['type' => 'string', 'description' => 'Phone number'],
                'company' => ['type' => 'string', 'description' => 'Company name'],
                'notes' => ['type' => 'string', 'description' => 'Additional notes'],
            ],
            'required' => ['first_name'],
        ];
    }

    public function getOutputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'success' => ['type' => 'boolean'],
                'customer_id' => ['type' => 'integer'],
                'customer_uuid' => ['type' => 'string'],
            ],
        ];
    }

    public function execute(array $parameters): array
    {
        $orgId = app('current_organization_id');

        $customer = Customer::create([
            'organization_id' => $orgId,
            'first_name' => $parameters['first_name'],
            'last_name' => $parameters['last_name'] ?? null,
            'email' => $parameters['email'] ?? null,
            'phone' => $parameters['phone'] ?? null,
            'company' => $parameters['company'] ?? null,
            'notes' => $parameters['notes'] ?? null,
            'source' => 'ai',
        ]);

        return $this->success('Customer created.', [
            'customer_id' => $customer->id,
            'customer_uuid' => $customer->uuid,
        ]);
    }
}

class GetCustomerTool extends BaseTool
{
    protected string $identifier = 'get_customer';
    protected string $name = 'Get Customer';
    protected string $description = 'Look up a customer by email, phone, or ID.';
    protected string $category = 'customers';

    public function getInputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'identifier' => ['type' => 'string', 'description' => 'Customer UUID, ID, email, or phone'],
            ],
            'required' => ['identifier'],
        ];
    }

    public function getOutputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'success' => ['type' => 'boolean'],
                'customer' => ['type' => 'object'],
            ],
        ];
    }

    public function execute(array $parameters): array
    {
        $orgId = app('current_organization_id');
        $id = $parameters['identifier'];

        $customer = Customer::where('organization_id', $orgId)
            ->where(fn ($q) => $q->where('uuid', $id)->orWhere('id', $id)->orWhere('email', $id)->orWhere('phone', $id))
            ->first();

        if (! $customer) return $this->error('Customer not found.');

        return $this->success('Customer found.', [
            'customer' => [
                'id' => $customer->id,
                'uuid' => $customer->uuid,
                'first_name' => $customer->first_name,
                'last_name' => $customer->last_name,
                'email' => $customer->email,
                'phone' => $customer->phone,
                'company' => $customer->company,
                'lead_stage' => $customer->lead_stage,
                'lead_score' => $customer->lead_score,
            ],
        ]);
    }
}