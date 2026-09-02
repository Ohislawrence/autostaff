<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Plugin;
use App\Models\PluginInstallation;
use App\Models\PluginVersion;
use App\Services\Api\ApiKeyService;
use App\Services\Plugins\PluginInstallationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PluginRuntimeTest extends TestCase
{
    use RefreshDatabase;

    protected Organization $organization;
    protected Plugin $plugin;
    protected string $apiKey;
    protected string $signingSecret;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organization = Organization::create([
            'name' => 'Runtime Org',
            'slug' => 'runtime-org',
            'onboarding_completed' => true,
        ]);

        $this->plugin = Plugin::create([
            'name' => 'WordPress Connect',
            'slug' => 'wordpress-connect',
            'is_published' => true,
        ]);

        PluginVersion::create([
            'plugin_id' => $this->plugin->id,
            'version' => '1.0.0',
            'file_path' => 'wordpress-connect/1.0.0/plugin.zip',
            'status' => 'published',
            'is_current' => true,
            'published_at' => now(),
        ]);

        $result = app(PluginInstallationService::class)->install(
            $this->organization,
            $this->plugin,
            'https://example.com'
        );

        $this->apiKey = $result['key'];
        $this->signingSecret = $result['signing_secret'];
    }

    protected function signedJson(string $method, string $path, array $data = []): \Illuminate\Testing\TestResponse
    {
        $headers = ['Authorization' => 'Bearer '.$this->apiKey];
        $timestamp = time();
        $json = json_encode($data);
        $canonical = implode("\n", [strtoupper($method), $path, (string) $timestamp, $json]);

        $headers['X-Timestamp'] = (string) $timestamp;
        $headers['X-Signature'] = hash_hmac('sha256', $canonical, $this->signingSecret);

        return $this->json($method, $path, $data, $headers);
    }

    #[Test]
    public function it_updates_last_seen_on_heartbeat()
    {
        $response = $this->signedJson('POST', '/api/v1/plugins/heartbeat');

        $response->assertOk();
        $response->assertJsonPath('status', 'ok');

        $installation = PluginInstallation::where('organization_id', $this->organization->id)->first();
        $this->assertNotNull($installation->last_seen_at);
    }

    #[Test]
    public function it_creates_a_lead_and_customer_via_api_key()
    {
        $response = $this->signedJson('POST', '/api/v1/leads', [
            'first_name' => 'Jane',
            'email' => 'jane@example.com',
            'stage' => 'new',
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('customers', [
            'email' => 'jane@example.com',
            'organization_id' => $this->organization->id,
        ]);
        $this->assertDatabaseHas('leads', ['stage' => 'new']);
    }

    #[Test]
    public function it_creates_an_order_with_items_via_api_key()
    {
        $response = $this->signedJson('POST', '/api/v1/orders', [
            'status' => 'processing',
            'currency' => 'USD',
            'total' => 59.99,
            'items' => [
                ['product_name' => 'T-shirt', 'quantity' => 2, 'unit_price' => 29.99, 'total_price' => 59.98],
            ],
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('orders', ['status' => 'processing', 'organization_id' => $this->organization->id]);
        $this->assertDatabaseHas('order_items', ['product_name' => 'T-shirt', 'quantity' => 2]);
    }

    #[Test]
    public function it_rejects_an_invalid_signature()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->apiKey,
            'X-Timestamp' => (string) time(),
            'X-Signature' => 'deadbeef',
        ])->postJson('/api/v1/leads', ['first_name' => 'X']);

        $response->assertStatus(401);
    }

    #[Test]
    public function it_rejects_a_request_missing_required_scope()
    {
        $result = app(ApiKeyService::class)->issue($this->organization, 'No scopes', []);

        $response = $this->withHeaders(['Authorization' => 'Bearer '.$result['key']])
            ->postJson('/api/v1/leads', ['first_name' => 'X']);

        $response->assertStatus(403);
    }

    #[Test]
    public function it_rejects_requests_without_a_key()
    {
        $this->postJson('/api/v1/leads', ['first_name' => 'X'])
            ->assertStatus(401);
    }

    #[Test]
    public function it_writes_audit_logs()
    {
        $this->assertDatabaseHas('audit_logs', ['event' => 'plugin_installed']);
        $this->assertDatabaseHas('audit_logs', ['event' => 'api_key_created']);
    }
}
