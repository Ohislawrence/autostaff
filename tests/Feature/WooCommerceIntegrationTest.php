<?php

namespace Tests\Feature;

use App\Ai\Tools\BuiltIn\CreateStoreOrderTool;
use App\Models\Customer;
use App\Models\Integration;
use App\Models\Order;
use App\Models\Organization;
use App\Models\Product;
use App\Services\Commerce\ProductSyncService;
use App\Services\Commerce\StoreConnector;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WooCommerceIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected Organization $organization;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organization = Organization::create([
            'name' => 'WooCommerce Merchant',
            'slug' => 'woo-merchant',
            'onboarding_completed' => true,
        ]);

        app()->instance('current_organization', $this->organization);
        app()->instance('current_organization_id', $this->organization->id);
    }

    protected function createWooIntegration(string $storeUrl = 'https://store.example.com', ?string $webhookSecret = null): Integration
    {
        return Integration::create([
            'organization_id' => $this->organization->id,
            'provider' => 'woocommerce',
            'name' => 'WooCommerce',
            'config' => [
                'store_url' => $storeUrl,
                'consumer_key' => Integration::encryptConfigValue('ck_test'),
                'consumer_secret' => Integration::encryptConfigValue('cs_test'),
                'webhook_secret' => $webhookSecret ? Integration::encryptConfigValue($webhookSecret) : null,
            ],
            'is_connected' => true,
            'status' => 'active',
        ]);
    }

    #[Test]
    public function it_syncs_woocommerce_products_into_the_local_catalog()
    {
        $this->createWooIntegration();

        Http::fake([
            'https://store.example.com/wp-json/wc/v3/products*' => Http::response([
                [
                    'id' => 101,
                    'name' => 'Blue T-Shirt',
                    'sku' => 'TSHIRT-BLUE',
                    'price' => '25.00',
                    'sale_price' => '',
                    'stock_quantity' => 10,
                    'categories' => [['name' => 'Clothing']],
                ],
                [
                    'id' => 102,
                    'name' => 'Red Mug',
                    'sku' => 'MUG-RED',
                    'price' => '12.50',
                    'sale_price' => '9.99',
                    'stock_quantity' => 5,
                    'categories' => [],
                ],
            ], 200),
        ]);

        $result = app(ProductSyncService::class)->sync($this->organization);

        $this->assertTrue($result['success']);
        $this->assertSame(2, $result['imported']);

        $product = Product::where('sku', 'TSHIRT-BLUE')->first();
        $this->assertNotNull($product);
        $this->assertEqualsWithDelta(25.0, (float) $product->price, 0.01);
        $this->assertSame('Clothing', $product->category);
        $this->assertSame('101', $product->metadata['external_store_id']);
        $this->assertSame(10, $product->inventory()->first()->quantity);
    }

    #[Test]
    public function it_creates_an_order_in_woocommerce()
    {
        $this->createWooIntegration();

        Http::fake([
            'https://store.example.com/wp-json/wc/v3/orders' => Http::response([
                'id' => 5001,
                'number' => '5001',
                'status' => 'pending',
                'total' => '75.00',
                'currency' => 'USD',
            ], 201),
        ]);

        $result = app(StoreConnector::class)->createOrder($this->organization, [
            'items' => [['product_id' => 101, 'quantity' => 2]],
            'billing' => ['email' => 'john@example.com'],
        ]);

        $this->assertTrue($result['success'], json_encode($result));
        $this->assertSame('5001', $result['external_id']);
        $this->assertSame('5001', $result['external_number']);
        $this->assertSame('pending', $result['status']);
    }

    #[Test]
    public function it_places_a_store_order_and_mirrors_it_locally()
    {
        $this->createWooIntegration();

        $customer = Customer::create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
        ]);

        $product = Product::create([
            'name' => 'Blue T-Shirt',
            'sku' => 'TSHIRT-BLUE',
            'price' => 25.00,
            'metadata' => ['external_store_id' => '101'],
        ]);

        Http::fake([
            'https://store.example.com/wp-json/wc/v3/orders' => Http::response([
                'id' => 5001,
                'number' => '5001',
                'status' => 'pending',
                'total' => '50.00',
                'currency' => 'USD',
            ], 201),
        ]);

        app()->instance('current_organization_id', $this->organization->id);

        $result = app(CreateStoreOrderTool::class)->execute([
            'customer_id' => $customer->id,
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
        ]);

        $this->assertTrue($result['success'], json_encode($result));

        $order = Order::first();
        $this->assertNotNull($order);
        $this->assertSame('5001', $order->metadata['external_order_id']);
        $this->assertSame('pending', $order->status);
    }

    #[Test]
    public function it_updates_a_local_order_from_a_woocommerce_webhook()
    {
        $this->createWooIntegration('https://store.example.com', 'secret123');

        $order = Order::create([
            'status' => 'pending',
            'total' => 50.00,
            'metadata' => ['external_order_id' => '5001'],
        ]);

        $rawBody = json_encode(['id' => 5001, 'status' => 'processing', 'total' => '50.00', 'currency' => 'USD']);
        $signature = base64_encode(hash_hmac('sha256', $rawBody, 'secret123', true));

        $response = $this->call('POST', '/api/v1/webhooks/woocommerce', [], [], [], [
            'HTTP_X_WC_WEBHOOK_SIGNATURE' => $signature,
            'HTTP_X_WC_WEBHOOK_SOURCE' => 'https://store.example.com',
            'HTTP_X_WC_WEBHOOK_TOPIC' => 'order.updated',
            'CONTENT_TYPE' => 'application/json',
        ], $rawBody);

        $response->assertOk();

        $order->refresh();
        $this->assertSame('processing', $order->status);
        $this->assertSame('paid', $order->payment_status);
    }

    #[Test]
    public function it_encrypts_and_decrypts_store_credentials()
    {
        $integration = Integration::create([
            'organization_id' => $this->organization->id,
            'provider' => 'woocommerce',
            'name' => 'WooCommerce',
            'config' => [
                'store_url' => 'https://store.example.com',
                'consumer_key' => Integration::encryptConfigValue('ck_secret'),
                'consumer_secret' => Integration::encryptConfigValue('cs_secret'),
            ],
            'is_connected' => true,
            'status' => 'active',
        ]);

        $this->assertStringStartsWith('encrypted:', $integration->config['consumer_key']);
        $this->assertSame('ck_secret', $integration->getConfigValue('consumer_key'));
        $this->assertSame('https://store.example.com', $integration->getConfigValue('store_url'));
    }

    #[Test]
    public function it_creates_an_order_in_shopify()
    {
        Integration::create([
            'organization_id' => $this->organization->id,
            'provider' => 'shopify',
            'name' => 'Shopify',
            'config' => [
                'shop_domain' => 'test.myshopify.com',
                'access_token' => Integration::encryptConfigValue('shpat_test'),
            ],
            'is_connected' => true,
            'status' => 'active',
        ]);

        Http::fake([
            'https://test.myshopify.com/admin/api/2024-01/orders.json' => Http::response([
                'order' => [
                    'id' => 9001,
                    'name' => '#1001',
                    'financial_status' => 'pending',
                    'total_price' => '30.00',
                    'currency' => 'USD',
                ],
            ], 201),
        ]);

        $result = app(StoreConnector::class)->createOrder($this->organization, [
            'items' => [['variant_id' => 111, 'quantity' => 1]],
            'billing' => ['email' => 'jane@example.com'],
        ]);

        $this->assertTrue($result['success'], json_encode($result));
        $this->assertSame('9001', $result['external_id']);
        $this->assertSame('pending', $result['status']);
    }

    #[Test]
    public function it_updates_product_stock_from_a_woocommerce_webhook()
    {
        $this->createWooIntegration('https://store.example.com', 'secret123');

        $product = Product::create([
            'name' => 'Old Name',
            'sku' => 'SKU-1',
            'price' => 10.00,
            'metadata' => ['external_store_id' => '101'],
        ]);

        $rawBody = json_encode([
            'id' => 101,
            'name' => 'New Name',
            'sku' => 'SKU-1',
            'price' => '20.00',
            'sale_price' => '',
            'stock_quantity' => 7,
            'status' => 'publish',
            'categories' => [['name' => 'New Cat']],
        ]);
        $signature = base64_encode(hash_hmac('sha256', $rawBody, 'secret123', true));

        $response = $this->call('POST', '/api/v1/webhooks/woocommerce', [], [], [], [
            'HTTP_X_WC_WEBHOOK_SIGNATURE' => $signature,
            'HTTP_X_WC_WEBHOOK_SOURCE' => 'https://store.example.com',
            'HTTP_X_WC_WEBHOOK_TOPIC' => 'product.updated',
            'CONTENT_TYPE' => 'application/json',
        ], $rawBody);

        $response->assertOk();

        $product->refresh();
        $this->assertSame('New Name', $product->name);
        $this->assertEqualsWithDelta(20.0, (float) $product->price, 0.01);
        $this->assertSame('New Cat', $product->category);
        $this->assertSame(7, $product->inventory()->first()->quantity);
    }

    #[Test]
    public function it_updates_a_product_in_woocommerce()
    {
        $this->createWooIntegration();

        Http::fake([
            'https://store.example.com/wp-json/wc/v3/products/101' => Http::response(['id' => 101], 200),
        ]);

        $result = app(StoreConnector::class)->updateProduct($this->organization, '101', [
            'name' => 'Updated Product',
            'price' => '30.00',
            'stock_quantity' => 3,
            'status' => 'publish',
        ]);

        $this->assertTrue($result['success'], json_encode($result));
    }

    #[Test]
    public function it_updates_a_shopify_product_variant_price()
    {
        Integration::create([
            'organization_id' => $this->organization->id,
            'provider' => 'shopify',
            'name' => 'Shopify',
            'config' => [
                'shop_domain' => 'test.myshopify.com',
                'access_token' => Integration::encryptConfigValue('shpat_test'),
            ],
            'is_connected' => true,
            'status' => 'active',
        ]);

        Http::fake([
            'https://test.myshopify.com/admin/api/2024-01/variants/111.json' => Http::response(['variant' => ['id' => 111]], 200),
        ]);

        $result = app(StoreConnector::class)->updateProduct($this->organization, '9001', [
            'external_variant_id' => 111,
            'price' => '45.00',
            'sale_price' => '39.00',
        ]);

        $this->assertTrue($result['success'], json_encode($result));
    }

    #[Test]
    public function it_updates_shopify_inventory()
    {
        Integration::create([
            'organization_id' => $this->organization->id,
            'provider' => 'shopify',
            'name' => 'Shopify',
            'config' => [
                'shop_domain' => 'test.myshopify.com',
                'access_token' => Integration::encryptConfigValue('shpat_test'),
            ],
            'is_connected' => true,
            'status' => 'active',
        ]);

        Http::fake([
            'https://test.myshopify.com/admin/api/2024-01/locations.json' => Http::response([
                'locations' => [['id' => 123]],
            ], 200),
            'https://test.myshopify.com/admin/api/2024-01/inventory_levels/set.json' => Http::response([
                'inventory_level' => ['available' => 8],
            ], 200),
        ]);

        $result = app(StoreConnector::class)->updateProduct($this->organization, '9001', [
            'external_variant_id' => 111,
            'external_inventory_item_id' => 222,
            'stock_quantity' => 8,
        ]);

        $this->assertTrue($result['success'], json_encode($result));
    }

    #[Test]
    public function it_updates_a_local_order_from_a_shopify_webhook()
    {
        Integration::create([
            'organization_id' => $this->organization->id,
            'provider' => 'shopify',
            'name' => 'Shopify',
            'config' => [
                'shop_domain' => 'test.myshopify.com',
                'access_token' => Integration::encryptConfigValue('shpat_test'),
                'webhook_secret' => Integration::encryptConfigValue('secret456'),
            ],
            'is_connected' => true,
            'status' => 'active',
        ]);

        $order = Order::create([
            'status' => 'pending',
            'total' => 50.00,
            'metadata' => ['external_order_id' => '9001'],
        ]);

        $rawBody = json_encode([
            'id' => 9001,
            'name' => '#1001',
            'financial_status' => 'paid',
            'fulfillment_status' => null,
            'cancelled_at' => null,
            'total_price' => '50.00',
            'currency' => 'USD',
        ]);
        $signature = base64_encode(hash_hmac('sha256', $rawBody, 'secret456', true));

        $response = $this->call('POST', '/api/v1/webhooks/shopify', [], [], [], [
            'HTTP_X_SHOPIFY_HMAC_SHA256' => $signature,
            'HTTP_X_SHOPIFY_SHOP_DOMAIN' => 'test.myshopify.com',
            'HTTP_X_SHOPIFY_TOPIC' => 'orders/paid',
            'CONTENT_TYPE' => 'application/json',
        ], $rawBody);

        $response->assertOk();

        $order->refresh();
        $this->assertSame('confirmed', $order->status);
        $this->assertSame('paid', $order->payment_status);
    }
}
