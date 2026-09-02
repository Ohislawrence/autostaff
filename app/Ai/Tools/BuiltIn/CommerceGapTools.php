<?php

namespace App\Ai\Tools\BuiltIn;

use App\Ai\Tools\BaseTool;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\Organization;
use App\Models\Product;
use App\Models\PromoCode;
use App\Models\Shipment;
use App\Models\SupportTicket;
use App\Services\Commerce\StoreConnector;

class AddToCartTool extends BaseTool
{
    protected string $identifier = 'add_to_cart';
    protected string $name = 'Add to Cart';
    protected string $description = 'Add a product to the customer\'s persistent cart.';
    protected string $category = 'commerce';

    public function getInputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'customer_id' => ['type' => 'string'],
                'product_id' => ['type' => 'string'],
                'quantity' => ['type' => 'integer', 'default' => 1],
                'product_variant_id' => ['type' => 'string'],
            ],
            'required' => ['customer_id', 'product_id'],
        ];
    }

    public function execute(array $parameters): array
    {
        $orgId = app('current_organization_id');
        $conversation = $parameters['_conversation'] ?? null;

        $customer = \App\Models\Customer::where('organization_id', $orgId)
            ->where(fn ($q) => $q->where('uuid', $parameters['customer_id'])->orWhere('id', $parameters['customer_id']))
            ->first();
        if (! $customer) return $this->error('Customer not found.');

        $product = Product::where('organization_id', $orgId)
            ->where(fn ($q) => $q->where('uuid', $parameters['product_id'])->orWhere('id', $parameters['product_id']))
            ->first();
        if (! $product) return $this->error('Product not found.');

        $quantity = max(1, (int) ($parameters['quantity'] ?? 1));
        $unitPrice = $product->sale_price ?: $product->price;

        $cart = Cart::firstOrCreate(
            ['organization_id' => $orgId, 'customer_id' => $customer->id, 'status' => 'active'],
            ['currency' => tenant_currency(), 'conversation_id' => $conversation?->id],
        );

        $existing = \App\Models\CartItem::where('cart_id', $cart->id)
            ->where('product_id', $product->id)
            ->first();

        if ($existing) {
            $existing->increment('quantity', $quantity);
            $existing->update(['total_price' => $existing->quantity * $unitPrice, 'unit_price' => $unitPrice]);
        } else {
            CartItem::create([
                'organization_id' => $orgId,
                'cart_id' => $cart->id,
                'product_id' => $product->id,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'total_price' => $unitPrice * $quantity,
            ]);
        }

        return $this->success("Added {$quantity} x {$product->name} to cart.", [
            'cart_uuid' => $cart->uuid,
            'subtotal' => (string) $cart->subtotal(),
        ]);
    }
}

class GetCartTool extends BaseTool
{
    protected string $identifier = 'get_cart';
    protected string $name = 'Get Cart';
    protected string $description = 'View the customer\'s current cart and total.';
    protected string $category = 'commerce';

    public function getInputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => ['customer_id' => ['type' => 'string']],
            'required' => ['customer_id'],
        ];
    }

    public function execute(array $parameters): array
    {
        $orgId = app('current_organization_id');
        $customer = \App\Models\Customer::where('organization_id', $orgId)
            ->where(fn ($q) => $q->where('uuid', $parameters['customer_id'])->orWhere('id', $parameters['customer_id']))
            ->first();
        if (! $customer) return $this->error('Customer not found.');

        $cart = Cart::where('organization_id', $orgId)
            ->where('customer_id', $customer->id)
            ->where('status', 'active')
            ->with('items.product')
            ->first();

        if (! $cart) return $this->success('Cart is empty.', ['items' => [], 'subtotal' => '0']);

        $items = $cart->items->map(fn ($i) => [
            'product' => $i->product?->name ?? 'Unknown',
            'quantity' => $i->quantity,
            'unit_price' => (string) $i->unit_price,
            'total_price' => (string) $i->total_price,
        ])->values();

        return $this->success('Cart retrieved.', [
            'cart_uuid' => $cart->uuid,
            'items' => $items,
            'subtotal' => (string) $cart->subtotal(),
            'currency' => $cart->currency,
        ]);
    }
}

class ApplyDiscountTool extends BaseTool
{
    protected string $identifier = 'apply_discount';
    protected string $name = 'Apply Discount';
    protected string $description = 'Apply a promo code to an order amount and return the discounted total.';
    protected string $category = 'commerce';

    public function getInputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'code' => ['type' => 'string'],
                'amount' => ['type' => 'number'],
            ],
            'required' => ['code', 'amount'],
        ];
    }

    public function execute(array $parameters): array
    {
        $orgId = app('current_organization_id');
        $promo = PromoCode::where('organization_id', $orgId)
            ->where('code', strtoupper(trim((string) $parameters['code'])))
            ->first();

        if (! $promo) return $this->error('Invalid promo code.');

        $amount = (float) $parameters['amount'];
        if (! $promo->isUsable($amount)) {
            return $this->error('Promo code is not applicable to this order.');
        }

        $discount = $promo->discountFor($amount);
        $total = round($amount - $discount, 2);

        return $this->success("Discount applied: {$promo->code}.", [
            'discount' => number_format($discount, 2, '.', ''),
            'original' => number_format($amount, 2, '.', ''),
            'total' => number_format($total, 2, '.', ''),
        ]);
    }
}

class TrackShipmentTool extends BaseTool
{
    protected string $identifier = 'track_shipment';
    protected string $name = 'Track Shipment';
    protected string $description = 'Look up the shipping status for an order.';
    protected string $category = 'orders';

    public function getInputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => ['order_identifier' => ['type' => 'string']],
            'required' => ['order_identifier'],
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

        $shipment = Shipment::where('organization_id', $orgId)
            ->where('order_id', $order->id)
            ->latest()
            ->first();

        if (! $shipment) {
            return $this->success("Order {$order->order_number} has not been shipped yet.", [
                'status' => 'not_shipped',
            ]);
        }

        $labels = [
            'pending' => 'preparing shipment',
            'dispatched' => 'dispatched',
            'in_transit' => 'in transit',
            'delivered' => 'delivered',
            'failed' => 'delivery failed',
        ];

        return $this->success("Shipment is " . ($labels[$shipment->status] ?? $shipment->status) . ".", [
            'status' => $shipment->status,
            'carrier' => $shipment->carrier,
            'tracking_number' => $shipment->tracking_number,
            'shipped_at' => $shipment->shipped_at?->toISOString(),
            'delivered_at' => $shipment->delivered_at?->toISOString(),
        ]);
    }
}

class CreateTicketTool extends BaseTool
{
    protected string $identifier = 'create_ticket';
    protected string $name = 'Create Ticket';
    protected string $description = 'Open a customer support ticket linked to the conversation.';
    protected string $category = 'support';

    public function getInputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'subject' => ['type' => 'string'],
                'description' => ['type' => 'string'],
                'priority' => ['type' => 'string', 'default' => 'normal'],
            ],
            'required' => ['subject'],
        ];
    }

    public function execute(array $parameters): array
    {
        $orgId = app('current_organization_id');
        $conversation = $parameters['_conversation'] ?? null;
        $customer = $conversation?->customer_id ? \App\Models\Customer::find($conversation->customer_id) : null;

        $ticket = SupportTicket::create([
            'organization_id' => $orgId,
            'customer_id' => $customer?->id,
            'conversation_id' => $conversation?->id,
            'subject' => $parameters['subject'],
            'description' => $parameters['description'] ?? null,
            'priority' => $parameters['priority'] ?? 'normal',
            'status' => 'open',
        ]);

        return $this->success('Support ticket created.', [
            'ticket_uuid' => $ticket->uuid,
            'status' => $ticket->status,
        ]);
    }
}

class UpdateTicketTool extends BaseTool
{
    protected string $identifier = 'update_ticket';
    protected string $name = 'Update Ticket';
    protected string $description = 'Update a support ticket status.';
    protected string $category = 'support';

    public function getInputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'ticket_identifier' => ['type' => 'string'],
                'status' => ['type' => 'string'],
            ],
            'required' => ['ticket_identifier', 'status'],
        ];
    }

    public function execute(array $parameters): array
    {
        $orgId = app('current_organization_id');
        $id = $parameters['ticket_identifier'];

        $ticket = SupportTicket::where('organization_id', $orgId)
            ->where(fn ($q) => $q->where('uuid', $id)->orWhere('id', $id))
            ->first();
        if (! $ticket) return $this->error('Ticket not found.');

        $ticket->update(['status' => $parameters['status']]);

        return $this->success("Ticket status updated to {$ticket->status}.", ['status' => $ticket->status]);
    }
}

class SearchStoreTool extends BaseTool
{
    protected string $identifier = 'search_store';
    protected string $name = 'Search Store';
    protected string $description = 'Search products in the connected Shopify or WooCommerce store.';
    protected string $category = 'commerce';

    public function getInputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => ['query' => ['type' => 'string']],
            'required' => ['query'],
        ];
    }

    public function execute(array $parameters): array
    {
        $organization = Organization::find(app('current_organization_id'));
        $connector = app(StoreConnector::class);

        $result = $connector->searchProducts($organization, (string) $parameters['query']);

        if (empty($result['success'])) {
            return $this->error($result['error'] ?? 'Store search failed.');
        }

        return $this->success('Store products found.', [
            'products' => $result['products'] ?? [],
        ]);
    }
}