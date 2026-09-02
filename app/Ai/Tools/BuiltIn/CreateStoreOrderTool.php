<?php

namespace App\Ai\Tools\BuiltIn;

use App\Ai\Tools\BaseTool;
use App\Models\Order;
use App\Services\Commerce\StoreConnector;

class CreateStoreOrderTool extends BaseTool
{
    protected string $identifier = 'create_store_order';
    protected string $name = 'Create Store Order';
    protected string $description = 'Place an order in the connected WooCommerce or Shopify store. Requires products to be synced first.';
    protected string $category = 'commerce';
    protected bool $requiresConfirmation = true;

    public function getInputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'customer_id' => ['type' => 'string', 'description' => 'Local customer UUID or ID'],
                'items' => [
                    'type' => 'array',
                    'description' => 'Order line items',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'product_id' => ['type' => 'string', 'description' => 'Local product UUID or ID'],
                            'quantity' => ['type' => 'integer', 'default' => 1],
                        ],
                    ],
                ],
                'customer_note' => ['type' => 'string', 'description' => 'Optional note for the order'],
            ],
            'required' => ['customer_id', 'items'],
        ];
    }

    public function getOutputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'success' => ['type' => 'boolean'],
                'order_id' => ['type' => 'integer'],
                'external_order_number' => ['type' => 'string'],
                'status' => ['type' => 'string'],
            ],
        ];
    }

    public function execute(array $parameters): array
    {
        $orgId = app('current_organization_id');

        $customer = \App\Models\Customer::where('organization_id', $orgId)
            ->where(fn ($q) => $q->where('uuid', $parameters['customer_id'])->orWhere('id', $parameters['customer_id']))
            ->first();
        if (! $customer) {
            return $this->error('Customer not found.');
        }

        $items = $parameters['items'] ?? [];
        if (empty($items)) {
            return $this->error('No items provided.');
        }

        $lineItems = [];
        foreach ($items as $item) {
            $product = \App\Models\Product::where('organization_id', $orgId)
                ->where(fn ($q) => $q->where('uuid', $item['product_id'])->orWhere('id', $item['product_id']))
                ->first();

            if (! $product) {
                continue;
            }

            $externalId = $product->metadata['external_store_id'] ?? null;
            if (! $externalId) {
                return $this->error("Product '{$product->name}' is not linked to a store product. Run the product sync first.");
            }

            $lineItems[] = [
                'product_id' => (int) $externalId,
                'variant_id' => isset($product->metadata['external_variant_id']) ? (int) $product->metadata['external_variant_id'] : null,
                'quantity' => max(1, (int) ($item['quantity'] ?? 1)),
            ];
        }

        if (empty($lineItems)) {
            return $this->error('No valid store products found.');
        }

        $organization = \App\Models\Organization::find($orgId);
        $result = app(StoreConnector::class)->createOrder($organization, [
            'items' => $lineItems,
            'billing' => [
                'first_name' => $customer->first_name,
                'last_name' => $customer->last_name,
                'email' => $customer->email,
                'phone' => $customer->phone,
            ],
            'customer_note' => $parameters['customer_note'] ?? null,
        ]);

        if (empty($result['success'])) {
            return $this->error($result['error'] ?? 'Store order creation failed.');
        }

        // Mirror the store order locally so it shows in the UI and webhooks can update it.
        $order = Order::create([
            'organization_id' => $orgId,
            'customer_id' => $customer->id,
            'conversation_id' => $parameters['_conversation'] ?? null,
            'ai_employee_id' => $parameters['_employee'] ?? null,
            'status' => $result['status'] ?? 'pending',
            'total' => (float) ($result['total'] ?? 0),
            'currency' => $result['currency'] ?? 'USD',
            'payment_status' => 'pending',
            'metadata' => [
                'external_order_id' => $result['external_id'] ?? null,
                'external_order_number' => $result['external_number'] ?? null,
                'provider' => 'woocommerce',
            ],
        ]);

        return $this->success('Store order created.', [
            'order_id' => $order->id,
            'external_order_number' => $result['external_number'] ?? null,
            'status' => $result['status'] ?? 'pending',
        ]);
    }
}
