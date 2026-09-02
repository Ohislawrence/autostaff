<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $orders = Order::with(['customer', 'items'])
            ->latest()
            ->paginate($request->integer('per_page', 25));

        return response()->json($orders);
    }

    public function show(Order $order)
    {
        abort_unless($order->organization_id === current_org_id(), 404);

        return response()->json($order->load(['customer', 'items']));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => ['nullable', 'integer'],
            'status' => ['nullable', 'string', 'max:50'],
            'subtotal' => ['nullable', 'numeric'],
            'tax' => ['nullable', 'numeric'],
            'shipping' => ['nullable', 'numeric'],
            'discount' => ['nullable', 'numeric'],
            'total' => ['nullable', 'numeric'],
            'currency' => ['nullable', 'string', 'max:3'],
            'payment_status' => ['nullable', 'string', 'max:50'],
            'payment_method' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
            'metadata' => ['nullable', 'array'],
            'shipping_address' => ['nullable', 'array'],
            'billing_address' => ['nullable', 'array'],
            'items' => ['nullable', 'array'],
            'items.*.product_name' => ['nullable', 'string', 'max:255'],
            'items.*.sku' => ['nullable', 'string', 'max:100'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['nullable', 'numeric'],
            'items.*.total_price' => ['nullable', 'numeric'],
        ]);

        if (! empty($validated['customer_id'])) {
            Customer::findOrFail($validated['customer_id']);
        }

        $items = $validated['items'] ?? [];
        unset($validated['items']);

        $order = Order::create($validated);

        foreach ($items as $item) {
            $order->items()->create([
                'product_name' => $item['product_name'] ?? null,
                'sku' => $item['sku'] ?? null,
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'] ?? 0,
                'total_price' => $item['total_price'] ?? 0,
                'metadata' => $item['metadata'] ?? null,
            ]);
        }

        return response()->json($order->load(['customer', 'items']), 201);
    }
}
