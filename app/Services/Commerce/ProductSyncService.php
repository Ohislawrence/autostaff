<?php

namespace App\Services\Commerce;

use App\Models\Inventory;
use App\Models\Organization;
use App\Models\Product;
use App\Support\Currency;
use Illuminate\Support\Facades\Log;

class ProductSyncService
{
    public function __construct(protected StoreConnector $connector) {}

    /**
     * Pull products from the connected WooCommerce/Shopify store into the
     * local catalog so the AI can search, price, and order them.
     */
    public function sync(Organization $organization, int $perPage = 100): array
    {
        app()->instance('current_organization_id', $organization->id);

        $imported = 0;
        $updated = 0;
        $page = 1;
        $count = 0;

        do {
            $result = $this->connector->listProducts($organization, $page, $perPage);

            if (empty($result['success'])) {
                Log::warning('Product sync failed', [
                    'organization_id' => $organization->id,
                    'error' => $result['error'] ?? 'Unknown error',
                ]);

                return ['success' => false, 'imported' => $imported, 'updated' => $updated, 'error' => $result['error'] ?? 'Unknown error'];
            }

            $products = $result['products'] ?? [];
            $count = count($products);

            foreach ($products as $p) {
                if ($this->upsertProduct($organization, $p)) {
                    $imported++;
                } else {
                    $updated++;
                }
            }

            $page++;
        } while ($count === $perPage);

        $store = $this->connector->getStore($organization);
        $store?->update(['last_synced_at' => now()]);

        return ['success' => true, 'imported' => $imported, 'updated' => $updated];
    }

    public function upsertProduct(Organization $organization, array $p): bool
    {
        app()->instance('current_organization_id', $organization->id);

        $sku = $p['sku'] ?? null;
        $externalId = $p['external_id'] ?? null;
        $externalVariantId = $p['external_variant_id'] ?? null;
        $externalInventoryItemId = $p['external_inventory_item_id'] ?? null;
        $currency = Currency::normalize($organization->currency);
        $category = ! empty($p['categories']) ? implode(', ', $p['categories']) : null;

        $product = null;
        if ($externalId !== null && $externalId !== '') {
            $product = Product::where('organization_id', $organization->id)
                ->where('metadata->external_store_id', (string) $externalId)
                ->first();
        }
        if (! $product && $sku) {
            $product = Product::where('organization_id', $organization->id)
                ->where('sku', $sku)
                ->first();
        }

        $metadata = array_merge($product?->metadata ?? [], [
            'external_store_id' => $externalId !== null ? (string) $externalId : null,
        ]);
        if ($externalVariantId !== null && $externalVariantId !== '') {
            $metadata['external_variant_id'] = (string) $externalVariantId;
        }
        if ($externalInventoryItemId !== null && $externalInventoryItemId !== '') {
            $metadata['external_inventory_item_id'] = (string) $externalInventoryItemId;
        }

        $data = [
            'name' => $p['name'] ?? 'Imported Product',
            'sku' => $sku,
            'price' => (float) ($p['price'] ?? 0),
            'sale_price' => (isset($p['sale_price']) && $p['sale_price'] !== null && $p['sale_price'] !== '') ? (float) $p['sale_price'] : null,
            'currency' => $currency,
            'category' => $category,
            'is_active' => $p['is_active'] ?? true,
            'metadata' => $metadata,
        ];

        if ($product) {
            $product->update($data);
            $created = false;
        } else {
            $product = Product::create(array_merge($data, ['organization_id' => $organization->id]));
            $created = true;
        }

        if (isset($p['stock_quantity'])) {
            Inventory::updateOrCreate(
                ['organization_id' => $organization->id, 'product_id' => $product->id],
                ['quantity' => (int) $p['stock_quantity']]
            );
        }

        return $created;
    }
}
