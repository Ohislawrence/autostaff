<?php

namespace App\Automation\Triggers;

use App\Automation\Contracts\TriggerInterface;
use App\Models\Order;

class OrderCreatedTrigger implements TriggerInterface
{
    public function identifier(): string
    {
        return 'order_created';
    }

    public function label(): string
    {
        return 'Order Created';
    }

    public function description(): string
    {
        return 'Fires when a new order is placed by a customer.';
    }

    public function configSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'min_total' => [
                    'type' => 'number',
                    'description' => 'Only fire for orders with total >= this value',
                ],
                'statuses' => [
                    'type' => 'array',
                    'description' => 'Only fire for specific statuses',
                    'items' => ['type' => 'string', 'enum' => ['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled']],
                ],
            ],
        ];
    }

    public function extractPayload(object|array $event): array
    {
        /** @var Order $order */
        $order = $event['order'];

        return [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'customer_id' => $order->customer_id,
            'customer_name' => $order->customer?->first_name . ' ' . $order->customer?->last_name,
            'customer_email' => $order->customer?->email,
            'total' => $order->total,
            'status' => $order->status,
            'items_count' => $order->items()->count(),
        ];
    }

    public function extractOrganizationId(object|array $event): int
    {
        return $event['order']->organization_id;
    }
}