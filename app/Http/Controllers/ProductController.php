<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Inventory;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $organization = current_org();
        $products = $organization->products()->with('inventory')
            ->when($request->search, fn ($q) => $q->where(fn ($q) => $q->where('name', 'like', "%{$request->search}%")->orWhere('sku', 'like', "%{$request->search}%")->orWhere('description', 'like', "%{$request->search}%")))
            ->when($request->category, fn ($q) => $q->where('category', $request->category))
            ->latest()->paginate(20)->withQueryString();
        $categories = $organization->products()->distinct()->pluck('category')->filter();
        return Inertia::render('Products/Index', ['products' => $products, 'categories' => $categories, 'filters' => $request->only(['search', 'category'])]);
    }

    public function store(Request $request)
    {
        $organization = current_org();
        $validated = $request->validate(['name' => 'required|string|max:255','description' => 'nullable|string','sku' => 'nullable|string|max:100','price' => 'required|numeric|min:0','sale_price' => 'nullable|numeric|min:0','currency' => 'nullable|string|max:10','category' => 'nullable|string|max:100','quantity' => 'nullable|integer|min:0']);
        $validated['currency'] = \App\Support\Currency::normalize($validated['currency'] ?? $organization->currency);
        $product = $organization->products()->create($validated);
        Inventory::create(['organization_id' => $organization->id, 'product_id' => $product->id, 'quantity' => $request->quantity ?? 0]);
        return back()->with('success', 'Product created.');
    }

    public function update(Request $request, Product $product)
    {
        $orgId = current_org_id(); if ($product->organization_id !== $orgId) abort(403);
        $validated = $request->validate(['name' => 'required|string|max:255','description' => 'nullable|string','sku' => 'nullable|string|max:100','price' => 'required|numeric|min:0','sale_price' => 'nullable|numeric|min:0','currency' => 'nullable|string|max:10','category' => 'nullable|string|max:100','quantity' => 'nullable|integer|min:0']);
        $validated['currency'] = \App\Support\Currency::normalize($validated['currency'] ?? $product->currency);
        $product->update($validated);
        if ($request->has('quantity')) { $inventory = $product->inventory()->first(); if ($inventory) $inventory->update(['quantity' => $request->quantity]); }

        // Push changes back to the connected store (app -> store) when this is a synced product.
        if (! empty($product->metadata['external_store_id'])) {
            try {
                app(\App\Services\Commerce\StoreConnector::class)->updateProduct(current_org(), (string) $product->metadata['external_store_id'], [
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'price' => $product->price,
                    'sale_price' => $product->sale_price,
                    'stock_quantity' => $request->has('quantity') ? (int) $request->quantity : null,
                    'status' => $product->is_active ? 'publish' : 'draft',
                    'external_variant_id' => $product->metadata['external_variant_id'] ?? null,
                    'external_inventory_item_id' => $product->metadata['external_inventory_item_id'] ?? null,
                ]);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Failed to push product update to store', ['product_id' => $product->id, 'error' => $e->getMessage()]);
            }
        }

        return back()->with('success', 'Product updated.');
    }

    public function destroy(Product $product)
    {
        $orgId = current_org_id(); if ($product->organization_id !== $orgId) abort(403);
        $product->delete(); return back()->with('success', 'Product deleted.');
    }
}