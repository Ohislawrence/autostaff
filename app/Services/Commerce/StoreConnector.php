<?php

namespace App\Services\Commerce;

use App\Models\Organization;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Connects to a tenant's external store (Shopify or WooCommerce) using the
 * credentials stored on the Integrations table, and exposes a unified surface
 * for product lookup, order lookup, and fulfillment.
 */
class StoreConnector
{
    /**
     * Get the connected store integration for an organization (if any).
     */
    public function getStore(Organization $organization): ?\App\Models\Integration
    {
        return \App\Models\Integration::where('organization_id', $organization->id)
            ->whereIn('provider', ['shopify', 'woocommerce'])
            ->where('is_connected', true)
            ->first();
    }

    public function searchProducts(Organization $organization, string $query, int $limit = 10): array
    {
        $store = $this->getStore($organization);
        if (! $store) {
            return ['success' => false, 'error' => 'No Shopify/WooCommerce store connected.'];
        }

        if ($store->provider === 'shopify') {
            return $this->shopifyProducts($store, $query, $limit);
        }

        return $this->wooProducts($store, $query, $limit);
    }

    public function getProduct(Organization $organization, string $identifier): array
    {
        $store = $this->getStore($organization);
        if (! $store) {
            return ['success' => false, 'error' => 'No Shopify/WooCommerce store connected.'];
        }

        return $store->provider === 'shopify'
            ? $this->shopifyProduct($store, $identifier)
            : $this->wooProduct($store, $identifier);
    }

    public function getOrder(Organization $organization, string $identifier): array
    {
        $store = $this->getStore($organization);
        if (! $store) {
            return ['success' => false, 'error' => 'No Shopify/WooCommerce store connected.'];
        }

        return $store->provider === 'shopify'
            ? $this->shopifyOrder($store, $identifier)
            : $this->wooOrder($store, $identifier);
    }

    protected function headers($store): array
    {
        $config = $store->config ?? [];

        if ($store->provider === 'shopify') {
            return [
                'X-Shopify-Access-Token' => $store->getConfigValue('access_token', ''),
                'Content-Type' => 'application/json',
            ];
        }

        return ['Content-Type' => 'application/json'];
    }

    protected function baseUrl($store): string
    {
        $config = $store->config ?? [];

        if ($store->provider === 'shopify') {
            $domain = rtrim($store->getConfigValue('shop_domain', ''), '/');
            return "https://{$domain}/admin/api/2024-01";
        }

        $url = rtrim($store->getConfigValue('store_url', ''), '/');
        return "{$url}/wp-json/wc/v3";
    }

    protected function shopifyProducts($store, string $query, int $limit): array
    {
        $url = $this->baseUrl($store) . '/products.json';
        $response = Http::withHeaders($this->headers($store))->get($url, [
            'title' => $query,
            'limit' => $limit,
            'status' => 'active',
        ]);

        if (! $response->ok()) {
            return ['success' => false, 'error' => 'Shopify request failed', 'code' => $response->status()];
        }

        $data = $response->json('products', []);
        $products = array_map(fn ($p) => [
            'external_id' => (string) ($p['id'] ?? ''),
            'title' => $p['title'] ?? '',
            'sku' => ($p['variants'][0]['sku'] ?? null),
            'price' => (string) ($p['variants'][0]['price'] ?? '0'),
        ], $data);

        return ['success' => true, 'products' => $products];
    }

    protected function wooProducts($store, string $query, int $limit): array
    {
        $url = $this->baseUrl($store) . '/products';
        $response = Http::withHeaders($this->headers($store))
            ->withBasicAuth(
                $store->getConfigValue('consumer_key', ''),
                $store->getConfigValue('consumer_secret', ''),
            )
            ->timeout(120)
            ->retry(2, 2000)
            ->get($url, ['search' => $query, 'per_page' => $limit]);

        if (! $response->ok()) {
            return ['success' => false, 'error' => 'WooCommerce request failed', 'code' => $response->status()];
        }

        $products = array_map(fn ($p) => [
            'external_id' => (string) ($p['id'] ?? ''),
            'title' => $p['name'] ?? '',
            'sku' => $p['sku'] ?? null,
            'price' => (string) ($p['price'] ?? '0'),
        ], $response->json());

        return ['success' => true, 'products' => $products];
    }

    protected function shopifyProduct($store, string $identifier): array
    {
        $response = Http::withHeaders($this->headers($store))
            ->get($this->baseUrl($store) . "/products/{$identifier}.json");

        if (! $response->ok()) {
            return ['success' => false, 'error' => 'Product not found'];
        }

        $p = $response->json('product', []);
        return ['success' => true, 'product' => [
            'external_id' => (string) ($p['id'] ?? ''),
            'title' => $p['title'] ?? '',
            'price' => (string) ($p['variants'][0]['price'] ?? '0'),
            'sku' => ($p['variants'][0]['sku'] ?? null),
        ]];
    }

    protected function wooProduct($store, string $identifier): array
    {
        $response = Http::withHeaders($this->headers($store))
            ->withBasicAuth($store->getConfigValue('consumer_key', ''), $store->getConfigValue('consumer_secret', ''))
            ->get($this->baseUrl($store) . "/products/{$identifier}");

        if (! $response->ok()) {
            return ['success' => false, 'error' => 'Product not found'];
        }

        $p = $response->json();
        return ['success' => true, 'product' => [
            'external_id' => (string) ($p['id'] ?? ''),
            'title' => $p['name'] ?? '',
            'price' => (string) ($p['price'] ?? '0'),
            'sku' => $p['sku'] ?? null,
        ]];
    }

    protected function shopifyOrder($store, string $identifier): array
    {
        $response = Http::withHeaders($this->headers($store))
            ->get($this->baseUrl($store) . "/orders/{$identifier}.json");

        if (! $response->ok()) {
            return ['success' => false, 'error' => 'Order not found'];
        }

        $o = $response->json('order', []);
        return ['success' => true, 'order' => [
            'external_id' => (string) ($o['id'] ?? ''),
            'number' => (string) ($o['name'] ?? ''),
            'status' => $o['financial_status'] ?? '',
            'total' => (string) ($o['total_price'] ?? '0'),
        ]];
    }

    protected function wooOrder($store, string $identifier): array
    {
        $response = Http::withHeaders($this->headers($store))
            ->withBasicAuth($store->getConfigValue('consumer_key', ''), $store->getConfigValue('consumer_secret', ''))
            ->get($this->baseUrl($store) . "/orders/{$identifier}");

        if (! $response->ok()) {
            return ['success' => false, 'error' => 'Order not found'];
        }

        $o = $response->json();
        return ['success' => true, 'order' => [
            'external_id' => (string) ($o['id'] ?? ''),
            'number' => (string) ($o['number'] ?? ''),
            'status' => $o['status'] ?? '',
            'total' => (string) ($o['total'] ?? '0'),
        ]];
    }

    public function listProducts(Organization $organization, int $page = 1, int $perPage = 100): array
    {
        $store = $this->getStore($organization);
        if (! $store) {
            return ['success' => false, 'error' => 'No Shopify/WooCommerce store connected.'];
        }

        return $store->provider === 'shopify'
            ? $this->shopifyProductList($store, $page, $perPage)
            : $this->wooProductList($store, $page, $perPage);
    }

    public function createOrder(Organization $organization, array $data): array
    {
        $store = $this->getStore($organization);
        if (! $store) {
            return ['success' => false, 'error' => 'No Shopify/WooCommerce store connected.'];
        }

        return $store->provider === 'shopify'
            ? $this->shopifyCreateOrder($store, $data)
            : $this->wooCreateOrder($store, $data);
    }

    protected function wooProductList($store, int $page, int $perPage): array
    {
        try {
            $response = Http::withBasicAuth(
                $store->getConfigValue('consumer_key', ''),
                $store->getConfigValue('consumer_secret', '')
            )
                ->timeout(120)
                ->retry(2, 2000)
                ->get($this->baseUrl($store).'/products', ['page' => $page, 'per_page' => $perPage]);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            return ['success' => false, 'error' => 'Could not reach your WooCommerce store. Check that the store URL is correct and reachable.'];
        }

        if ($response->status() === 401 || $response->status() === 403) {
            return ['success' => false, 'error' => 'WooCommerce authentication failed. Recreate the REST API key with Read access and re-enter the Consumer Key and Secret.', 'code' => $response->status()];
        }

        if (! $response->ok()) {
            return ['success' => false, 'error' => 'WooCommerce request failed', 'code' => $response->status()];
        }

        $products = array_map(fn ($p) => [
            'external_id' => (string) ($p['id'] ?? ''),
            'name' => $p['name'] ?? '',
            'sku' => $p['sku'] ?? null,
            'price' => (string) ($p['price'] ?? '0'),
            'sale_price' => (isset($p['sale_price']) && $p['sale_price'] !== '') ? (string) $p['sale_price'] : null,
            'stock_quantity' => isset($p['stock_quantity']) ? (int) $p['stock_quantity'] : null,
            'categories' => array_map(fn ($c) => $c['name'] ?? '', $p['categories'] ?? []),
        ], $response->json());

        return ['success' => true, 'products' => $products, 'page' => $page];
    }

    protected function shopifyProductList($store, int $page, int $perPage): array
    {
        $response = Http::withHeaders($this->headers($store))
            ->get($this->baseUrl($store).'/products.json', ['limit' => $perPage, 'page' => $page]);

        if (! $response->ok()) {
            return ['success' => false, 'error' => 'Shopify request failed', 'code' => $response->status()];
        }

        $products = array_map(fn ($p) => [
            'external_id' => (string) ($p['id'] ?? ''),
            'name' => $p['title'] ?? '',
            'sku' => $p['variants'][0]['sku'] ?? null,
            'external_variant_id' => (string) ($p['variants'][0]['id'] ?? ''),
            'external_inventory_item_id' => (string) ($p['variants'][0]['inventory_item_id'] ?? ''),
            'price' => (string) ($p['variants'][0]['price'] ?? '0'),
            'sale_price' => null,
            'stock_quantity' => null,
            'categories' => [],
        ], $response->json('products', []));

        return ['success' => true, 'products' => $products, 'page' => $page];
    }

    protected function wooCreateOrder($store, array $data): array
    {
        $payload = [
            'payment_method' => 'ai',
            'payment_method_title' => 'AI Employee Order',
            'set_paid' => false,
            'status' => 'pending',
            'billing' => [
                'first_name' => $data['billing']['first_name'] ?? '',
                'last_name' => $data['billing']['last_name'] ?? '',
                'email' => $data['billing']['email'] ?? '',
                'phone' => $data['billing']['phone'] ?? '',
                'address_1' => $data['billing']['address_1'] ?? '',
                'city' => $data['billing']['city'] ?? '',
                'postcode' => $data['billing']['postcode'] ?? '',
            ],
            'line_items' => array_map(fn ($item) => [
                'product_id' => $item['product_id'] ?? 0,
                'quantity' => max(1, (int) ($item['quantity'] ?? 1)),
            ], $data['items'] ?? []),
            'customer_note' => $data['customer_note'] ?? null,
            'meta_data' => [
                ['key' => '_ai_employee_source', 'value' => 'nomdal'],
            ],
        ];

        $response = Http::withBasicAuth(
            $store->getConfigValue('consumer_key', ''),
            $store->getConfigValue('consumer_secret', '')
        )->post($this->baseUrl($store).'/orders', $payload);

        if (! $response->successful()) {
            return [
                'success' => false,
                'error' => 'WooCommerce order creation failed',
                'code' => $response->status(),
            ];
        }

        $o = $response->json();

        return [
            'success' => true,
            'external_id' => (string) ($o['id'] ?? ''),
            'external_number' => (string) ($o['number'] ?? ''),
            'status' => $o['status'] ?? 'pending',
            'total' => (string) ($o['total'] ?? '0'),
            'currency' => $o['currency'] ?? 'USD',
        ];
    }

    protected function shopifyCreateOrder($store, array $data): array
    {
        $payload = [
            'order' => [
                'line_items' => array_map(fn ($item) => [
                    'variant_id' => $item['variant_id'] ?? $item['product_id'] ?? 0,
                    'quantity' => max(1, (int) ($item['quantity'] ?? 1)),
                ], $data['items'] ?? []),
                'email' => $data['billing']['email'] ?? null,
                'financial_status' => 'pending',
                'note' => $data['customer_note'] ?? null,
            ],
        ];

        $response = Http::withHeaders($this->headers($store))
            ->post($this->baseUrl($store).'/orders.json', $payload);

        if (! $response->successful()) {
            return [
                'success' => false,
                'error' => 'Shopify order creation failed',
                'code' => $response->status(),
            ];
        }

        $o = $response->json('order', []);

        return [
            'success' => true,
            'external_id' => (string) ($o['id'] ?? ''),
            'external_number' => (string) ($o['name'] ?? ''),
            'status' => $o['financial_status'] ?? 'pending',
            'total' => (string) ($o['total_price'] ?? '0'),
            'currency' => $o['currency'] ?? 'USD',
        ];
    }

    public function updateProduct(Organization $organization, string $externalId, array $data): array
    {
        $store = $this->getStore($organization);
        if (! $store) {
            return ['success' => false, 'error' => 'No Shopify/WooCommerce store connected.'];
        }

        return $store->provider === 'shopify'
            ? $this->shopifyUpdateProduct($store, $externalId, $data)
            : $this->wooUpdateProduct($store, $externalId, $data);
    }

    protected function wooUpdateProduct($store, string $externalId, array $data): array
    {
        $payload = array_filter([
            'name' => $data['name'] ?? null,
            'sku' => $data['sku'] ?? null,
            'price' => $data['price'] ?? null,
            'sale_price' => $data['sale_price'] ?? null,
            'stock_quantity' => $data['stock_quantity'] ?? null,
            'status' => $data['status'] ?? null,
        ], fn ($v) => $v !== null);

        if (empty($payload)) {
            return ['success' => true, 'skipped' => true];
        }

        $response = Http::withBasicAuth(
            $store->getConfigValue('consumer_key', ''),
            $store->getConfigValue('consumer_secret', '')
        )->put($this->baseUrl($store).'/products/'.$externalId, $payload);

        if (! $response->successful()) {
            return [
                'success' => false,
                'error' => 'WooCommerce product update failed',
                'code' => $response->status(),
            ];
        }

        return ['success' => true, 'external_id' => $externalId];
    }

    protected function shopifyUpdateProduct($store, string $externalId, array $data): array
    {
        $variantId = $data['external_variant_id'] ?? null;
        if (! $variantId) {
            return ['success' => false, 'error' => 'Shopify variant ID is required to update a product.'];
        }

        $variant = [];
        if (isset($data['price'])) {
            $variant['price'] = (string) $data['price'];
        }
        if (isset($data['sale_price']) && $data['sale_price'] !== null && $data['sale_price'] !== '') {
            $variant['compare_at_price'] = (string) $data['sale_price'];
        }

        if (! empty($variant)) {
            $response = Http::withHeaders($this->headers($store))
                ->put($this->baseUrl($store).'/variants/'.$variantId.'.json', ['variant' => $variant]);

            if (! $response->successful()) {
                return [
                    'success' => false,
                    'error' => 'Shopify product update failed',
                    'code' => $response->status(),
                ];
            }
        }

        if (isset($data['stock_quantity'])) {
            $inventoryItemId = $data['external_inventory_item_id'] ?? null;
            if (! $inventoryItemId) {
                return ['success' => false, 'error' => 'Shopify inventory item ID is required to update stock.'];
            }

            $locationId = $this->shopifyLocationId($store);
            if (! $locationId) {
                return ['success' => false, 'error' => 'No Shopify location found to update inventory.'];
            }

            $response = Http::withHeaders($this->headers($store))
                ->post($this->baseUrl($store).'/inventory_levels/set.json', [
                    'location_id' => $locationId,
                    'inventory_item_id' => $inventoryItemId,
                    'available' => (int) $data['stock_quantity'],
                ]);

            if (! $response->successful()) {
                return [
                    'success' => false,
                    'error' => 'Shopify inventory update failed',
                    'code' => $response->status(),
                ];
            }
        }

        return ['success' => true, 'variant_id' => $variantId];
    }

    protected function shopifyLocationId($store): ?string
    {
        $response = Http::withHeaders($this->headers($store))
            ->get($this->baseUrl($store).'/locations.json');

        if (! $response->successful()) {
            return null;
        }

        $locations = $response->json('locations', []);

        return $locations[0]['id'] ?? null;
    }
}