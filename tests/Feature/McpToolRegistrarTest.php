<?php

namespace Tests\Feature;

use App\Ai\Tools\ToolInterface;
use App\Mcp\McpToolHandler;
use App\Models\McpConnection;
use App\Models\Organization;
use App\Models\Tool;
use App\Services\Mcp\McpConnectionManager;
use App\Services\Mcp\McpToolInvoker;
use App\Services\Mcp\McpToolRegistrar;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class McpToolRegistrarTest extends TestCase
{
    use RefreshDatabase;

    protected Organization $orgA;
    protected Organization $orgB;
    protected McpConnectionManager $connections;
    protected McpToolRegistrar $registrar;

    protected function setUp(): void
    {
        parent::setUp();

        $this->orgA = Organization::create(['name' => 'A', 'slug' => 'a', 'onboarding_completed' => true]);
        $this->orgB = Organization::create(['name' => 'B', 'slug' => 'b', 'onboarding_completed' => true]);
        $this->connections = new McpConnectionManager();
        $this->registrar = new McpToolRegistrar($this->connections, new McpToolInvoker($this->connections));
    }

    protected function fakeMcpServer(array $tools, ?array $callResult = null): void
    {
        Http::fake(function ($request) use ($tools, $callResult) {
            $body = $request->data();
            $method = $body['method'] ?? '';
            $id = $body['id'] ?? 0;

            $result = match ($method) {
                'initialize' => ['serverInfo' => ['name' => 'demo']],
                'tools/list' => ['tools' => $tools],
                'tools/call' => $callResult ?? ['content' => [], 'isError' => false],
                default => [],
            };

            return Http::response(['jsonrpc' => '2.0', 'id' => $id, 'result' => $result]);
        });
    }

    protected function makeConnection(Organization $org): McpConnection
    {
        return $this->connections->store($org->id, [
            'provider' => 'google_calendar',
            'endpoint' => 'https://mcp.example.com',
            'credentials' => ['access_token' => 'secret'],
        ]);
    }

    #[Test] public function it_syncs_mcp_tools_into_tenant_scoped_tool_rows()
    {
        $connection = $this->makeConnection($this->orgA);
        $this->fakeMcpServer([
            ['name' => 'create_event', 'description' => 'Create a calendar event', 'inputSchema' => ['type' => 'object']],
            ['name' => 'list_events', 'description' => 'List events'],
        ]);

        $count = $this->registrar->sync($connection);

        $this->assertSame(2, $count);

        $tool = Tool::where('organization_id', $this->orgA->id)
            ->where('is_custom', true)
            ->where('name', 'create_event')
            ->first();

        $this->assertNotNull($tool);
        $this->assertSame(McpToolHandler::class, $tool->handler_class);
        $this->assertSame($connection->id, $tool->custom_config['mcp_connection_id']);
        $this->assertSame('create_event', $tool->custom_config['mcp_tool']);
        $this->assertStringStartsWith('mcp_', $tool->identifier);
    }

    #[Test] public function it_isolates_tools_between_tenants()
    {
        $connA = $this->makeConnection($this->orgA);
        $connB = $this->makeConnection($this->orgB);
        $this->fakeMcpServer([['name' => 'create_event', 'description' => 'Create event']]);

        $this->registrar->sync($connA);
        $this->registrar->sync($connB);

        $toolsA = Tool::where('organization_id', $this->orgA->id)->where('is_custom', true)->get();
        $toolsB = Tool::where('organization_id', $this->orgB->id)->where('is_custom', true)->get();

        $this->assertCount(1, $toolsA);
        $this->assertCount(1, $toolsB);
        $this->assertNotSame($toolsA->first()->identifier, $toolsB->first()->identifier);
    }

    #[Test] public function it_resolves_a_working_handler_for_the_owning_tenant()
    {
        $connection = $this->makeConnection($this->orgA);
        $this->fakeMcpServer(
            [['name' => 'create_event', 'description' => 'Create event', 'inputSchema' => ['type' => 'object']]],
            ['content' => [['type' => 'text', 'text' => 'Event created']], 'isError' => false],
        );

        $this->registrar->sync($connection);

        $tool = Tool::where('organization_id', $this->orgA->id)->where('is_custom', true)->first();

        $handler = $this->registrar->resolve($tool->identifier, $this->orgA->id);

        $this->assertInstanceOf(McpToolHandler::class, $handler);
        $this->assertInstanceOf(ToolInterface::class, $handler);

        $result = $handler->execute(['title' => 'Meeting']);

        $this->assertTrue($result['success']);
        $this->assertSame('Event created', $result['message']);
    }

    #[Test] public function it_does_not_resolve_another_tenants_tool()
    {
        $connA = $this->makeConnection($this->orgA);
        $this->fakeMcpServer([['name' => 'create_event', 'description' => 'Create event']]);
        $this->registrar->sync($connA);

        $tool = Tool::where('organization_id', $this->orgA->id)->where('is_custom', true)->first();

        $this->assertNull($this->registrar->resolve($tool->identifier, $this->orgB->id));
    }

    #[Test] public function it_removes_tools_for_a_connection()
    {
        $connection = $this->makeConnection($this->orgA);
        $this->fakeMcpServer([['name' => 'create_event', 'description' => 'Create event']]);
        $this->registrar->sync($connection);

        $removed = $this->registrar->removeForConnection($connection);

        $this->assertSame(1, $removed);
        $this->assertSame(0, Tool::where('organization_id', $this->orgA->id)->where('is_custom', true)->count());
    }
}
