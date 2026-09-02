<?php

namespace Tests\Feature;

use App\Http\Controllers\McpConnectionController;
use App\Models\McpConnection;
use App\Models\Organization;
use App\Models\Tool;
use App\Models\ToolExecution;
use App\Services\Mcp\McpConnectionManager;
use App\Services\Mcp\McpToolRegistrar;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class McpConnectionControllerTest extends TestCase
{
    use RefreshDatabase;

    protected Organization $organization;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organization = Organization::create([
            'name' => 'MCP UI Org',
            'slug' => 'mcp-ui-org',
            'onboarding_completed' => true,
        ]);
        app()->instance('current_organization', $this->organization);
        app()->instance('current_organization_id', $this->organization->id);
    }

    protected function fakeServer(array $tools = []): void
    {
        Http::fake(function ($request) use ($tools) {
            $method = $request->data()['method'] ?? '';
            $id = $request->data()['id'] ?? 0;

            $result = match ($method) {
                'initialize' => ['serverInfo' => ['name' => 'demo']],
                'tools/list' => ['tools' => $tools],
                'tools/call' => ['content' => [['type' => 'text', 'text' => 'ok']], 'isError' => false],
                default => [],
            };

            return Http::response(['jsonrpc' => '2.0', 'id' => $id, 'result' => $result]);
        });
    }

    #[Test] public function it_stores_connection_and_syncs_tools()
    {
        $this->fakeServer([
            ['name' => 'create_event', 'description' => 'Create event', 'inputSchema' => ['type' => 'object']],
        ]);

        $controller = app(McpConnectionController::class);
        $response = $controller->store(new Request([
            'provider' => 'google_calendar',
            'endpoint' => 'https://mcp.example.com',
            'credentials' => ['access_token' => 'secret'],
        ]));

        $this->assertStringContainsString('/integrations/mcp', $response->getTargetUrl());
        $this->assertSame(1, McpConnection::where('organization_id', $this->organization->id)->count());
        $this->assertSame(1, Tool::where('organization_id', $this->organization->id)->where('is_custom', true)->count());
    }

    #[Test] public function it_disconnects_and_removes_tools()
    {
        $this->fakeServer([
            ['name' => 'create_event', 'description' => 'Create event'],
        ]);

        $connection = app(McpConnectionManager::class)->store($this->organization->id, [
            'provider' => 'google_calendar',
            'endpoint' => 'https://mcp.example.com',
            'credentials' => ['access_token' => 'secret'],
        ]);
        app(McpToolRegistrar::class)->sync($connection);

        $this->assertSame(1, Tool::where('organization_id', $this->organization->id)->where('is_custom', true)->count());

        app(McpConnectionController::class)->disconnect($connection);

        $this->assertSame(0, Tool::where('organization_id', $this->organization->id)->where('is_custom', true)->count());
        $this->assertFalse($connection->fresh()->is_connected);
    }

    #[Test] public function it_lists_connections_and_recent_calls()
    {
        $this->fakeServer([
            ['name' => 'create_event', 'description' => 'Create event'],
        ]);

        $connection = app(McpConnectionManager::class)->store($this->organization->id, [
            'provider' => 'google_calendar',
            'endpoint' => 'https://mcp.example.com',
            'credentials' => ['access_token' => 'secret'],
        ]);
        app(McpToolRegistrar::class)->sync($connection);

        $tool = Tool::where('organization_id', $this->organization->id)->where('is_custom', true)->first();
        ToolExecution::create([
            'tool_id' => $tool->id,
            'status' => 'success',
            'input_parameters' => [],
            'estimated_cost' => 0.005,
        ]);

        $response = app(McpConnectionController::class)->index();

        $component = (fn () => $this->component)->call($response);
        $props = (fn () => $this->props)->call($response);

        $this->assertSame('Integrations/Mcp', $component);
        $this->assertCount(1, $props['connections']);
        $this->assertSame(1, $props['connections'][0]['tool_count']);
        $this->assertCount(1, $props['recentCalls']);
    }
}
