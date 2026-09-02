<?php

namespace App\Ai\Tools\BuiltIn;

use App\Ai\Tools\BaseTool;
use App\Models\Order;
use App\Models\Product;
use App\Services\Payments\PaystackService;
use App\Support\Currency;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GenerateQuotationTool extends BaseTool
{
    protected string $identifier = 'generate_quotation';
    protected string $name = 'Generate Quotation';
    protected string $description = 'Create a formal quotation/quote for a customer from product prices. Applies discount rules and outputs a formatted summary.';
    protected string $category = 'sales';
    protected bool $requiresConfirmation = true;

    public function getInputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'customer_id' => ['type' => 'string', 'description' => 'Customer UUID or ID'],
                'items' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'product_id' => ['type' => 'string'],
                            'quantity' => ['type' => 'integer', 'default' => 1],
                        ],
                    ],
                ],
                'discount_percent' => ['type' => 'number', 'description' => 'Optional discount percentage (0-100)'],
                'notes' => ['type' => 'string'],
                'valid_days' => ['type' => 'integer', 'default' => 7, 'description' => 'Days quotation is valid for'],
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
                'quotation_number' => ['type' => 'string'],
                'total' => ['type' => 'string'],
                'formatted_summary' => ['type' => 'string'],
            ],
        ];
    }

    public function execute(array $parameters): array
    {
        $orgId = app('current_organization_id');
        $currency = tenant_currency();
        $symbol = Currency::symbol($currency);

        $customer = \App\Models\Customer::where('organization_id', $orgId)
            ->where(fn ($q) => $q->where('uuid', $parameters['customer_id'])->orWhere('id', $parameters['customer_id']))
            ->first();
        if (! $customer) return $this->error('Customer not found.');

        $items = $parameters['items'] ?? [];
        if (empty($items)) return $this->error('No items provided.');

        $quotationItems = [];
        $subtotal = 0;

        foreach ($items as $item) {
            $product = Product::where('organization_id', $orgId)
                ->where(fn ($q) => $q->where('uuid', $item['product_id'])->orWhere('id', $item['product_id']))
                ->first();
            if (! $product) continue;

            $qty = max(1, (int) ($item['quantity'] ?? 1));
            $price = $product->sale_price ?: $product->price;
            $lineTotal = $price * $qty;
            $subtotal += $lineTotal;

            $quotationItems[] = [
                'product_name' => $product->name,
                'sku' => $product->sku,
                'quantity' => $qty,
                'unit_price' => (string) $price,
                'total_price' => (string) $lineTotal,
            ];
        }

        $discountPercent = min(100, max(0, (float) ($parameters['discount_percent'] ?? 0)));
        $discountAmount = round($subtotal * ($discountPercent / 100), 2);
        $total = round($subtotal - $discountAmount, 2);

        $quotation = DB::table('quotations')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'organization_id' => $orgId,
            'customer_id' => $customer->id,
            'items' => json_encode($quotationItems),
            'subtotal' => $subtotal,
            'discount_percent' => $discountPercent,
            'discount_amount' => $discountAmount,
            'total' => $total,
            'currency' => $currency,
            'status' => 'draft',
            'notes' => $parameters['notes'] ?? null,
            'valid_until' => now()->addDays((int) ($parameters['valid_days'] ?? 7)),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $itemLines = '';
        foreach ($quotationItems as $qi) {
            $itemLines .= "\n  {$qi['product_name']} × {$qi['quantity']} — {$symbol}{$qi['total_price']}";
        }

        $discountLine = $discountPercent > 0 ? "\n  Discount: {$discountPercent}% (-{$symbol}{$discountAmount})" : '';

        $formattedSummary = "📋 *Quotation #{$quotation}*\n" .
            "For: {$customer->first_name} {$customer->last_name}\n" .
            "Items:{$itemLines}{$discountLine}\n" .
            "━━━━━━━━━━━━━━━\n" .
            "*Total: {$symbol}{$total}*\n" .
            "Valid until: " . now()->addDays((int) ($parameters['valid_days'] ?? 7))->format('d M, Y');

        return $this->success("Quotation generated. Total: {$symbol}{$total}", [
            'quotation_id' => $quotation,
            'quotation_number' => "QTN-{$quotation}",
            'subtotal' => (string) $subtotal,
            'discount_amount' => (string) $discountAmount,
            'total' => (string) $total,
            'formatted_summary' => $formattedSummary,
            'item_count' => count($quotationItems),
        ]);
    }
}

class GenerateInvoiceTool extends BaseTool
{
    protected string $identifier = 'generate_invoice';
    protected string $name = 'Generate Invoice';
    protected string $description = 'Generate an invoice from an order or quotation with a Paystack payment link. Returns a shareable WhatsApp-formatted message.';
    protected string $category = 'payments';
    protected bool $requiresConfirmation = true;

    public function getInputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'order_id' => ['type' => 'string', 'description' => 'Order UUID, ID, or order number (optional if order_identifier provided)'],
                'order_identifier' => ['type' => 'string', 'description' => 'Order UUID, ID, or order number'],
                'customer_email' => ['type' => 'string', 'description' => 'Customer email for payment receipt'],
            ],
            'required' => ['customer_email'],
        ];
    }

    public function getOutputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'success' => ['type' => 'boolean'],
                'invoice_number' => ['type' => 'string'],
                'payment_link' => ['type' => 'string'],
                'whatsapp_message' => ['type' => 'string'],
            ],
        ];
    }

    public function execute(array $parameters): array
    {
        $orgId = app('current_organization_id');

        // Find the order
        $orderIdentifier = $parameters['order_identifier'] ?? $parameters['order_id'] ?? null;
        $order = null;

        if ($orderIdentifier) {
            $order = Order::where('organization_id', $orgId)
                ->where(fn ($q) => $q->where('uuid', $orderIdentifier)->orWhere('id', $orderIdentifier)->orWhere('order_number', $orderIdentifier))
                ->with(['items', 'customer'])
                ->first();
        }

        if (! $order) {
            return $this->error('Order not found. Provide a valid order_id or order_identifier.');
        }

        if ($order->payment_status === 'paid') {
            return $this->error('This order has already been paid.');
        }

        $customer = $order->customer;
        $customerEmail = $parameters['customer_email'] ?? $customer?->email ?? 'customer@example.com';

        // Create invoice record
        $invoiceItems = $order->items->map(fn ($item) => [
            'product_name' => $item->product_name ?? 'Product',
            'quantity' => $item->quantity,
            'unit_price' => (string) $item->unit_price,
            'total_price' => (string) $item->total_price,
        ])->toArray();

        $invoiceId = DB::table('invoices')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'organization_id' => $orgId,
            'customer_id' => $order->customer_id,
            'order_id' => $order->id,
            'items' => json_encode($invoiceItems),
            'subtotal' => $order->subtotal ?? $order->total,
            'total' => $order->total,
            'currency' => $order->currency ?? tenant_currency(),
            'status' => 'pending',
            'payment_gateway' => 'paystack',
            'notes' => $parameters['notes'] ?? null,
            'due_date' => now()->addDays(3),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Generate Paystack payment link
        $paystack = app(PaystackService::class);
        $reference = $paystack->generateReference('INV');

        $paymentResult = $paystack->generatePaymentLink([
            'email' => $customerEmail,
            'amount' => $order->total,
            'currency' => $order->currency ?? tenant_currency(),
            'reference' => $reference,
            'invoice_id' => $invoiceId,
            'order_id' => $order->id,
            'organization_id' => $orgId,
        ]);

        if ($paymentResult['success']) {
            DB::table('invoices')->where('id', $invoiceId)->update([
                'payment_link' => $paymentResult['authorization_url'],
                'status' => 'pending',
            ]);

            DB::table('payments')->insert([
                'uuid' => (string) Str::uuid(),
                'organization_id' => $orgId,
                'customer_id' => $order->customer_id,
                'invoice_id' => $invoiceId,
                'order_id' => $order->id,
                'gateway' => 'paystack',
                'gateway_reference' => $reference,
                'amount' => $order->total,
                'currency' => $order->currency ?? tenant_currency(),
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $shortLink = $paymentResult['authorization_url'] ?? 'Payment link not available — Paystack not configured.';

        $whatsappMessage = "🧾 *Invoice #{$invoiceId}*\n" .
            "Order: {$order->order_number}\n" .
            "Total: " . format_money($order->total, $order->currency) . "\n" .
            "Pay here: {$shortLink}\n\n" .
            "After payment, reply with your payment reference or screenshot. ✅";

        return $this->success("Invoice #{$invoiceId} created.", [
            'invoice_id' => $invoiceId,
            'invoice_number' => "INV-{$invoiceId}",
            'order_number' => $order->order_number,
            'total' => (string) $order->total,
            'payment_link' => $shortLink,
            'whatsapp_message' => $whatsappMessage,
        ]);
    }
}

class RecordPaymentTool extends BaseTool
{
    protected string $identifier = 'record_payment';
    protected string $name = 'Record Payment';
    protected string $description = 'Verify and record a payment via Paystack reference. Marks the order as paid.';
    protected string $category = 'payments';
    protected bool $requiresConfirmation = true;

    public function getInputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'reference' => ['type' => 'string', 'description' => 'Paystack transaction reference'],
                'order_identifier' => ['type' => 'string', 'description' => 'Order UUID, ID, or order number (optional — auto-detected from Paystack metadata)'],
            ],
            'required' => ['reference'],
        ];
    }

    public function getOutputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'success' => ['type' => 'boolean'],
                'verified' => ['type' => 'boolean'],
                'amount' => ['type' => 'string'],
                'message' => ['type' => 'string'],
            ],
        ];
    }

    public function execute(array $parameters): array
    {
        $orgId = app('current_organization_id');
        $reference = $parameters['reference'];

        // Check if already recorded
        $existing = DB::table('payments')
            ->where('organization_id', $orgId)
            ->where('gateway_reference', $reference)
            ->where('status', 'verified')
            ->first();

        if ($existing) {
            return $this->success("Payment {$reference} was already recorded.", [
                'verified' => true,
                'amount' => (string) $existing->amount,
            ]);
        }

        // Verify with Paystack
        $paystack = app(PaystackService::class);
        $verification = $paystack->verifyTransaction($reference);

        if (! ($verification['success'] ?? false)) {
            return $this->error('Could not verify payment reference: ' . ($verification['error'] ?? 'Unknown error'));
        }

        if (! ($verification['verified'] ?? false)) {
            return $this->error('Payment reference ' . $reference . ' has not been paid yet. Status: ' . ($verification['gateway_response'] ?? 'pending'));
        }

        $amount = $verification['amount'];
        $metadata = $verification['metadata'] ?? [];

        // Find associated order
        $orderId = $parameters['order_identifier'] ?? $metadata['order_id'] ?? null;
        $invoiceId = $metadata['invoice_id'] ?? null;

        if ($orderId) {
            $order = Order::where('organization_id', $orgId)
                ->where(fn ($q) => $q->where('uuid', $orderId)->orWhere('id', $orderId)->orWhere('order_number', $orderId))
                ->first();

            if ($order && $order->payment_status !== 'paid') {
                $order->update([
                    'payment_status' => 'paid',
                    'status' => $order->status === 'pending' ? 'confirmed' : $order->status,
                ]);
            }
        }

        // Update or create payment record
        DB::table('payments')->updateOrInsert(
            ['gateway_reference' => $reference, 'organization_id' => $orgId],
            [
                'uuid' => (string) Str::uuid(),
                'organization_id' => $orgId,
                'customer_id' => $order->customer_id ?? null,
                'invoice_id' => $invoiceId,
                'order_id' => $order->id ?? null,
                'gateway' => 'paystack',
                'gateway_reference' => $reference,
                'gateway_status' => $verification['gateway_response'] ?? 'success',
                'amount' => $amount,
                'currency' => $verification['currency'] ?? tenant_currency(),
                'status' => 'verified',
                'gateway_response' => json_encode($verification),
                'paid_at' => $verification['paid_at'] ?? now(),
                'updated_at' => now(),
            ]
        );

        // Update invoice if found
        if ($invoiceId) {
            DB::table('invoices')->where('id', $invoiceId)->update([
                'status' => 'paid',
                'paid_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $this->success("Payment of " . format_money($amount, $verification['currency'] ?? tenant_currency()) . " verified and recorded. Order has been marked as paid. ✅", [
            'verified' => true,
            'amount' => (string) $amount,
            'reference' => $reference,
            'order_number' => $order->order_number ?? null,
        ]);
    }
}

class SendFollowupTool extends BaseTool
{
    protected string $identifier = 'send_followup';
    protected string $name = 'Send Follow-up';
    protected string $description = 'Schedule a follow-up message to be sent to a customer at a future time. Use this to nudge customers who asked about pricing but didn\'t commit, or to check in after a sale.';
    protected string $category = 'engagement';

    public function getInputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'customer_id' => ['type' => 'string', 'description' => 'Customer UUID or ID'],
                'message' => ['type' => 'string', 'description' => 'Follow-up message content'],
                'send_in_hours' => ['type' => 'integer', 'description' => 'Send in N hours from now'],
                'send_in_days' => ['type' => 'integer', 'description' => 'Send in N days from now (alternative to hours)'],
                'channel' => ['type' => 'string', 'default' => 'whatsapp', 'description' => 'Channel to send on'],
            ],
            'required' => ['customer_id', 'message'],
        ];
    }

    public function getOutputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'success' => ['type' => 'boolean'],
                'scheduled_id' => ['type' => 'integer'],
                'send_at' => ['type' => 'string'],
            ],
        ];
    }

    public function execute(array $parameters): array
    {
        $orgId = app('current_organization_id');

        $customer = \App\Models\Customer::where('organization_id', $orgId)
            ->where(fn ($q) => $q->where('uuid', $parameters['customer_id'])->orWhere('id', $parameters['customer_id']))
            ->first();
        if (! $customer) return $this->error('Customer not found.');

        // Calculate send_at
        if (! empty($parameters['send_in_days'])) {
            $sendAt = now()->addDays((int) $parameters['send_in_days']);
        } elseif (! empty($parameters['send_in_hours'])) {
            $sendAt = now()->addHours((int) $parameters['send_in_hours']);
        } else {
            $sendAt = now()->addDay(); // Default: tomorrow
        }

        $recipient = $customer->phone ?: $customer->email;

        $scheduledId = DB::table('scheduled_messages')->insertGetId([
            'organization_id' => $orgId,
            'customer_id' => $customer->id,
            'channel' => $parameters['channel'] ?? 'whatsapp',
            'recipient' => $recipient,
            'content' => $parameters['message'],
            'status' => 'pending',
            'send_at' => $sendAt,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $this->success("Follow-up scheduled for {$sendAt->format('d M, H:i')}", [
            'scheduled_id' => $scheduledId,
            'send_at' => $sendAt->toISOString(),
            'recipient' => $recipient,
        ]);
    }
}