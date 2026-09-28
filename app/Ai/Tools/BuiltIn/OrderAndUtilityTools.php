<?php

namespace App\Ai\Tools\BuiltIn;

use App\Ai\Tools\BaseTool;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Task;
use App\Services\Automation\AutomationService;

class CreateOrderTool extends BaseTool
{
    protected string $identifier = 'create_order';
    protected string $name = 'Create Order';
    protected string $description = 'Create a new order for a customer. Requires customer ID and items.';
    protected string $category = 'orders';
    protected bool $requiresConfirmation = true;

    public function getInputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'customer_id' => ['type' => 'string', 'description' => 'Customer UUID or ID'],
                'items' => [
                    'type' => 'array',
                    'description' => 'Array of items to order',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'product_id' => ['type' => 'string'],
                            'quantity' => ['type' => 'integer', 'default' => 1],
                        ],
                    ],
                ],
                'notes' => ['type' => 'string', 'description' => 'Order notes'],
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
                'order_number' => ['type' => 'string'],
                'total' => ['type' => 'string'],
            ],
        ];
    }

    public function execute(array $parameters): array
    {
        $orgId = app('current_organization_id');

        // Lifecycle linkage: record originating employee + conversation.
        $employee = $parameters['_employee'] ?? null;
        $conversation = $parameters['_conversation'] ?? null;

        $customer = \App\Models\Customer::where('organization_id', $orgId)
            ->where(fn ($q) => $q->where('uuid', $parameters['customer_id'])->orWhere('id', $parameters['customer_id']))
            ->first();

        if (! $customer) return $this->error('Customer not found.');

        $items = $parameters['items'] ?? [];
        if (empty($items)) return $this->error('No items provided.');

        $subtotal = 0;
        $orderItems = [];

        foreach ($items as $item) {
            $product = \App\Models\Product::where('organization_id', $orgId)
                ->where(fn ($q) => $q->where('uuid', $item['product_id'])->orWhere('id', $item['product_id']))
                ->first();

            if (! $product) continue;

            $qty = max(1, (int) ($item['quantity'] ?? 1));
            $price = $product->sale_price ?: $product->price;
            $lineTotal = $price * $qty;
            $subtotal += $lineTotal;

            $orderItems[] = [
                'organization_id' => $orgId,
                'product_id' => $product->id,
                'product_name' => $product->name,
                'sku' => $product->sku,
                'quantity' => $qty,
                'unit_price' => $price,
                'total_price' => $lineTotal,
            ];
        }

        $order = Order::create([
            'organization_id' => $orgId,
            'customer_id' => $customer->id,
            'conversation_id' => $conversation?->id,
            'ai_employee_id' => $employee?->id,
            'status' => 'pending',
            'subtotal' => $subtotal,
            'total' => $subtotal,
            'currency' => tenant_currency(),
            'notes' => $parameters['notes'] ?? null,
            'payment_status' => 'pending',
        ]);

        foreach ($orderItems as $oi) {
            $order->items()->create($oi);
        }

        // Fire automation trigger for the newly created order.
        try {
            app(AutomationService::class)->triggerOnOrderCreated($order);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('Automation trigger failed in CreateOrderTool', [
                'error' => $e->getMessage(),
            ]);
        }

        return $this->success("Order #{$order->order_number} created with total {$subtotal}.", [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'order_uuid' => $order->uuid,
            'total' => (string) $subtotal,
            'item_count' => count($orderItems),
        ]);
    }
}

class GetOrderTool extends BaseTool
{
    protected string $identifier = 'get_order';
    protected string $name = 'Get Order';
    protected string $description = 'Look up order details by order number or ID.';
    protected string $category = 'orders';

    public function getInputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'order_identifier' => ['type' => 'string', 'description' => 'Order UUID, ID, or order number'],
            ],
            'required' => ['order_identifier'],
        ];
    }

    public function getOutputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'success' => ['type' => 'boolean'],
                'order' => ['type' => 'object'],
            ],
        ];
    }

    public function execute(array $parameters): array
    {
        $orgId = app('current_organization_id');
        $id = $parameters['order_identifier'];

        $order = Order::where('organization_id', $orgId)
            ->where(fn ($q) => $q->where('uuid', $id)->orWhere('id', $id)->orWhere('order_number', $id))
            ->with('items')
            ->first();

        if (! $order) return $this->error('Order not found.');

        return $this->success('Order found.', [
            'order' => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'status' => $order->status,
                'total' => (string) $order->total,
                'currency' => $order->currency,
                'payment_status' => $order->payment_status,
                'item_count' => $order->items->count(),
                'created_at' => $order->created_at->toISOString(),
            ],
        ]);
    }
}

class GetOrderStatusTool extends BaseTool
{
    protected string $identifier = 'get_order_status';
    protected string $name = 'Get Order Status';
    protected string $description = 'Check the current status of an order.';
    protected string $category = 'orders';

    public function getInputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'order_identifier' => ['type' => 'string'],
            ],
            'required' => ['order_identifier'],
        ];
    }

    public function getOutputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'success' => ['type' => 'boolean'],
                'status' => ['type' => 'string'],
                'order_number' => ['type' => 'string'],
            ],
        ];
    }

    public function execute(array $parameters): array
    {
        $orgId = app('current_organization_id');
        $id = $parameters['order_identifier'];

        $order = Order::where('organization_id', $orgId)
            ->where(fn ($q) => $q->where('uuid', $id)->orWhere('id', $id)->orWhere('order_number', $id))
            ->first();

        if (! $order) return $this->error('Order not found.');

        $statusLabels = [
            'pending' => 'pending processing',
            'confirmed' => 'confirmed',
            'processing' => 'being processed',
            'shipped' => 'shipped',
            'delivered' => 'delivered',
            'cancelled' => 'cancelled',
            'refunded' => 'refunded',
        ];

        $label = $statusLabels[$order->status] ?? $order->status;

        return $this->success("Order #{$order->order_number} is {$label}.", [
            'status' => $order->status,
            'status_label' => $label,
            'order_number' => $order->order_number,
        ]);
    }
}

class CancelOrderTool extends BaseTool
{
    protected string $identifier = 'cancel_order';
    protected string $name = 'Cancel Order';
    protected string $description = 'Cancel an order that is in pending or confirmed status.';
    protected string $category = 'orders';
    protected bool $requiresConfirmation = true;

    public function getInputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'order_identifier' => ['type' => 'string'],
                'reason' => ['type' => 'string', 'description' => 'Cancellation reason'],
            ],
            'required' => ['order_identifier'],
        ];
    }

    public function getOutputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'success' => ['type' => 'boolean'],
                'message' => ['type' => 'string'],
            ],
        ];
    }

    public function execute(array $parameters): array
    {
        $orgId = app('current_organization_id');
        $id = $parameters['order_identifier'];

        $order = Order::where('organization_id', $orgId)
            ->where(fn ($q) => $q->where('uuid', $id)->orWhere('id', $id)->orWhere('order_number', $id))
            ->first();

        if (! $order) return $this->error('Order not found.');

        if (! in_array($order->status, ['pending', 'confirmed'])) {
            return $this->error("Order cannot be cancelled because it is {$order->status}.");
        }

        $order->update(['status' => 'cancelled', 'notes' => ($order->notes ? $order->notes . "\n" : '') . 'Cancellation: ' . ($parameters['reason'] ?? 'Customer requested cancellation')]);

        return $this->success("Order #{$order->order_number} has been cancelled.", [
            'order_number' => $order->order_number,
        ]);
    }
}

class TransferToHumanTool extends BaseTool
{
    protected string $identifier = 'transfer_to_human';
    protected string $name = 'Transfer to Human';
    protected string $description = 'Transfer the current conversation to a human agent. Use this when you cannot resolve an issue, the customer requests it, or the situation requires human intervention.';
    protected string $category = 'escalation';

    public function getInputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'reason' => ['type' => 'string', 'description' => 'Why the conversation should be transferred'],
                'summary' => ['type' => 'string', 'description' => 'Summary of the conversation for the human agent'],
            ],
            'required' => ['reason'],
        ];
    }

    public function getOutputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'success' => ['type' => 'boolean'],
                'message' => ['type' => 'string'],
            ],
        ];
    }

    public function execute(array $parameters): array
    {
        $conversation = $parameters['_conversation'] ?? null;
        $reason = $parameters['reason'] ?? 'Customer requested human assistance';
        $summary = $parameters['summary'] ?? null;

        // Actually flag the conversation for human attention (status -> human_required).
        if ($conversation) {
            try {
                app(\App\Services\ConversationService::class)
                    ->escalateToHuman($conversation, $reason, $summary);
            } catch (\Throwable $e) {
                // A handoff failure shouldn't break the AI's response.
            }
        }

        return $this->success('Transfer initiated. A human agent will take over.', [
            'reason' => $reason,
            'transfer_initiated' => true,
        ]);
    }
}

class CreateTaskTool extends BaseTool
{
    protected string $identifier = 'create_task';
    protected string $name = 'Create Task';
    protected string $description = 'Create a task for the team to follow up on.';
    protected string $category = 'tasks';

    public function getInputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'title' => ['type' => 'string', 'description' => 'Task title'],
                'description' => ['type' => 'string', 'description' => 'Task description'],
                'priority' => ['type' => 'string', 'description' => 'low, normal, high, or urgent'],
            ],
            'required' => ['title'],
        ];
    }

    public function getOutputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'success' => ['type' => 'boolean'],
                'task_id' => ['type' => 'integer'],
            ],
        ];
    }

    public function execute(array $parameters): array
    {
        $orgId = app('current_organization_id');

        $task = Task::create([
            'organization_id' => $orgId,
            'title' => $parameters['title'],
            'description' => $parameters['description'] ?? null,
            'priority' => $parameters['priority'] ?? 'normal',
            'status' => 'open',
        ]);

        return $this->success('Task created.', ['task_id' => $task->id, 'task_uuid' => $task->uuid]);
    }
}