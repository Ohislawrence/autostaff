<?php

namespace Tests\Feature;

use App\Http\Controllers\McpOAuthController;
use App\Models\McpConnection;
use App\Models\Organization;
use App\Models\Tool;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class McpOAuthTest extends TestCase
{
    use RefreshDatabase;

    protected Organization $organization;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organization = Organization::create([
            'name' => 'OAuth Org',
            'slug' => 'oauth-org',
            'onboarding_completed' => true,
        ]);
        app()->instance('current_organization', $this->organization);
        app()->instance('current_organization_id', $this->organization->id);

        config([
            'mcp.enabled' => true,
            'mcp.allowed_providers' => ['google_calendar', 'gmail', 'crm', 'microsoft_365', 'custom'],
            'mcp.oauth.google' => [
                'client_id' => 'test-client-id',
                'client_secret' => 'test-client-secret',
                'redirect_uri' => 'http://localhost/integrations/mcp/oauth/callback',
                'authorize_url' => 'https://accounts.google.com/o/oauth2/v2/auth',
                'token_url' => 'https://oauth2.googleapis.com/token',
                'endpoint' => 'https://mcp.googleapis.com',
                'authorize_params' => ['access_type' => 'offline', 'prompt' => 'consent'],
                'scopes' => [
                    'google_calendar' => ['https://www.googleapis.com/auth/calendar'],
                    'gmail' => ['https://www.googleapis.com/auth/gmail.send'],
                ],
            ],
        ]);
    }

    protected function fakeOAuthAndMcp(): void
    {
        Http::fake(function ($request) {
            $url = $request->url();

            if (str_contains($url, 'oauth2.googleapis.com/token')) {
                return Http::response([
                    'access_token' => 'google-access',
                    'refresh_token' => 'google-refresh',
                    'expires_in' => 3600,
                ]);
            }

            $method = $request->data()['method'] ?? '';
            $id = $request->data()['id'] ?? 0;

            $result = match ($method) {
                'initialize' => ['serverInfo' => ['name' => 'demo']],
                'tools/list' => ['tools' => [['name' => 'create_event', 'description' => 'Create event', 'inputSchema' => ['type' => 'object']]]],
                'tools/call' => ['content' => [['type' => 'text', 'text' => 'ok']], 'isError' => false],
                default => [],
            };

            return Http::response(['jsonrpc' => '2.0', 'id' => $id, 'result' => $result]);
        });
    }

    #[Test] public function it_redirects_to_google_with_state()
    {
        $response = app(McpOAuthController::class)->redirect('google_calendar');

        $url = $response->getTargetUrl();

        $this->assertStringContainsString('accounts.google.com', $url);
        $this->assertStringContainsString('client_id=test-client-id', $url);
        $this->assertStringContainsString('state=', $url);
        $this->assertStringContainsString('response_type=code', $url);

        $this->assertNotNull(session()->get('mcp_oauth_state'));
        $this->assertSame('google_calendar', session()->get('mcp_oauth_provider'));
    }

    #[Test] public function it_exchanges_code_and_stores_connection()
    {
        $state = 'state-123';
        session()->put('mcp_oauth_state', $state);
        session()->put('mcp_oauth_provider', 'google_calendar');

        $this->fakeOAuthAndMcp();

        $response = app(McpOAuthController::class)->callback(new Request([
            'state' => $state,
            'code' => 'auth-code-123',
        ]));

        $this->assertStringContainsString('/integrations/mcp', $response->getTargetUrl());

        $connection = McpConnection::where('organization_id', $this->organization->id)->first();
        $this->assertNotNull($connection);
        $this->assertSame('google_calendar', $connection->provider);
        $this->assertSame('google-access', $connection->credentials['access_token']);
        $this->assertSame('google-refresh', $connection->credentials['refresh_token']);
        $this->assertNotNull($connection->expires_at);

        $this->assertSame(1, Tool::where('organization_id', $this->organization->id)->where('is_custom', true)->count());
    }

    #[Test] public function it_rejects_state_mismatch()
    {
        session()->put('mcp_oauth_state', 'expected-state');
        session()->put('mcp_oauth_provider', 'google_calendar');

        app(McpOAuthController::class)->callback(new Request([
            'state' => 'wrong-state',
            'code' => 'auth-code-123',
        ]));

        $this->assertSame(0, McpConnection::count());
    }

    #[Test] public function it_handles_token_exchange_failure()
    {
        session()->put('mcp_oauth_state', 'state-123');
        session()->put('mcp_oauth_provider', 'google_calendar');

        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response('boom', 400),
        ]);

        app(McpOAuthController::class)->callback(new Request([
            'state' => 'state-123',
            'code' => 'auth-code-123',
        ]));

        $this->assertSame(0, McpConnection::count());
    }
}
