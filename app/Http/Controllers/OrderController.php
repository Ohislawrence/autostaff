<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Inertia\Inertia;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $organization = app('current_organization');

        $orders = $organization->orders()
            ->with(['customer', 'items.product'])
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->search, fn ($q) => $q->where(fn ($q) => $q->where('order_number', 'like', "%{$request->search}%")->orWhereHas('customer', fn ($q) => $q->where('first_name', 'like', "%{$request->search}%")->orWhere('last_name', 'like', "%{$request->search}%"))))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $stats = [
            'total' => $organization->orders()->count(),
            'pending' => $organization->orders()->where('status', 'pending')->count(),
            'completed' => $organization->orders()->whereIn('status', ['delivered', 'shipped'])->count(),
            'revenue' => (string) $organization->orders()->whereIn('status', ['confirmed', 'processing', 'shipped', 'delivered'])->sum('total'),
        ];

        return Inertia::render('Orders/Index', [
            'orders' => $orders,
            'stats' => $stats,
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    public function show(Order $order)
    {
        $organization = app('current_organization');
        if ($order->organization_id !== $organization->id) abort(403);

        $order->load(['customer', 'items.product', 'aiEmployee', 'conversation']);

        return Inertia::render('Orders/Show', ['order' => $order]);
    }

    public function updateStatus(Request $request, Order $order)
    {
        $organization = app('current_organization');
        if ($order->organization_id !== $organization->id) abort(403);

        $request->validate(['status' => 'required|in:pending,confirmed,processing,shipped,delivered,cancelled']);

        $order->update(['status' => $request->status]);

        return back()->with('success', "Order #{$order->order_number} updated to {$request->status}.");
    }
}