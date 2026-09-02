<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Services\Mcp\McpConnectionManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class McpConnectionManagerTest extends TestCase
{
    use RefreshDatabase;

    protected Organization $orgA;
    protected Organization $orgB;
    protected McpConnectionManager $manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->orgA = Organization::create(['name' => 'Org A', 'slug' => 'org-a', 'onboarding_completed' => true]);
        $this->orgB = Organization::create(['name' => 'Org B', 'slug' => 'org-b', 'onboarding_completed' => true]);

        $this->manager = new McpConnectionManager();
    }

    #[Test] public function it_stores_credentials_encrypted_at_rest()
    {
        $connection = $this->manager->store($this->orgA->id, [
            'provider' => 'google_calendar',
            'endpoint' => 'https://mcp.example.com',
            'credentials' => ['access_token' => 'super-secret-token', 'refresh_token' => 'refresh-123'],
        ]);

        $raw = DB::table('mcp_connections')->where('id', $connection->id)->value('credentials');

        $this->assertNotNull($raw);
        $this->assertStringNotContainsString('super-secret-token', $raw);
        $this->assertStringNotContainsString('refresh-123', $raw);

        $this->assertSame('super-secret-token', $connection->fresh()->credentials['access_token']);
    }

    #[Test] public function it_isolates_connections_between_tenants()
    {
        $connA = $this->manager->store($this->orgA->id, [
            'provider' => 'google_calendar',
            'credentials' => ['access_token' => 'A'],
        ]);
        $this->manager->store($this->orgB->id, [
            'provider' => 'google_calendar',
            'credentials' => ['access_token' => 'B'],
        ]);

        $this->assertCount(1, $this->manager->forOrganization($this->orgA->id));
        $this->assertCount(1, $this->manager->forOrganization($this->orgB->id));
        $this->assertSame($connA->id, $this->manager->forOrganization($this->orgA->id)->first()->id);
        $this->assertNull($this->manager->find($this->orgB->id, $connA->id));
    }

    #[Test] public function it_builds_http_transport_config_with_credentials()
    {
        $connection = $this->manager->store($this->orgA->id, [
            'provider' => 'google_calendar',
            'endpoint' => 'https://mcp.example.com',
            'credentials' => ['access_token' => 'secret'],
        ]);

        $config = $this->manager->buildTransportConfig($connection);

        $this->assertSame('https://mcp.example.com', $config->endpoint);
        $this->assertSame('Bearer secret', $config->headers['Authorization']);
    }

    #[Test] public function it_disconnects_and_revokes_credentials()
    {
        $connection = $this->manager->store($this->orgA->id, [
            'provider' => 'google_calendar',
            'credentials' => ['access_token' => 'secret'],
        ]);

        $this->manager->disconnect($connection);

        $fresh = $connection->fresh();
        $this->assertFalse($fresh->is_connected);
        $this->assertSame('disconnected', $fresh->status);
        $this->assertNull($fresh->credentials);
    }

    #[Test] public function it_health_checks_successfully()
    {
        Http::fake([
            'https://mcp.example.com*' => Http::sequence()
                ->push(['jsonrpc' => '2.0', 'id' => 1, 'result' => ['serverInfo' => ['name' => 'demo']]])
                ->push(['jsonrpc' => '2.0', 'id' => 2, 'result' => []]),
        ]);

        $connection = $this->manager->store($this->orgA->id, [
            'provider' => 'google_calendar',
            'endpoint' => 'https://mcp.example.com',
            'credentials' => ['access_token' => 'secret'],
        ]);

        $result = $this->manager->healthCheck($connection);

        $this->assertTrue($result['success']);

        $fresh = $connection->fresh();
        $this->assertTrue($fresh->is_connected);
        $this->assertSame('connected', $fresh->status);
        $this->assertNotNull($fresh->last_health_check_at);
        $this->assertNull($fresh->last_error);
    }

    #[Test] public function it_health_checks_failure()
    {
        Http::fake([
            'https://mcp.example.com*' => Http::response('boom', 500),
        ]);

        $connection = $this->manager->store($this->orgA->id, [
            'provider' => 'google_calendar',
            'endpoint' => 'https://mcp.example.com',
            'credentials' => ['access_token' => 'secret'],
        ]);

        $result = $this->manager->healthCheck($connection);

        $this->assertFalse($result['success']);

        $fresh = $connection->fresh();
        $this->assertSame('error', $fresh->status);
        $this->assertNotNull($fresh->last_error);
    }

    #[Test] public function it_refreshes_tokens()
    {
        Http::fake([
            'https://oauth.example.com/token' => Http::response([
                'access_token' => 'new-access',
                'refresh_token' => 'new-refresh',
                'expires_in' => 3600,
            ]),
        ]);

        $connection = $this->manager->store($this->orgA->id, [
            'provider' => 'google_calendar',
            'endpoint' => 'https://mcp.example.com',
            'credentials' => [
                'access_token' => 'old-access',
                'refresh_token' => 'old-refresh',
                'client_id' => 'client',
                'client_secret' => 'secret',
            ],
        ]);

        $refreshed = $this->manager->refreshTokens($connection, 'https://oauth.example.com/token');

        $this->assertSame('new-access', $refreshed->credentials['access_token']);
        $this->assertSame('new-refresh', $refreshed->credentials['refresh_token']);
        $this->assertNotNull($refreshed->expires_at);
    }
}

