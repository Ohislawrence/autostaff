<?php

namespace Tests\Feature;

use App\Mcp\McpException;
use App\Mcp\McpTool;
use App\Mcp\McpToolHandler;
use App\Models\McpConnection;
use App\Models\Organization;
use App\Services\Mcp\McpConnectionManager;
use App\Services\Mcp\McpToolInvoker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class McpToolInvokerTest extends TestCase
{
    use RefreshDatabase;

    protected Organization $org;
    protected McpConnectionManager $connections;

    protected function setUp(): void
    {
        parent::setUp();

        $this->org = Organization::create(['name' => 'Invoker Org', 'slug' => 'invoker-org', 'onboarding_completed' => true]);
        $this->connections = new McpConnectionManager();
    }

    protected function makeConnection(string $provider, array $overrides = []): McpConnection
    {
        return $this->connections->store($this->org->id, array_merge([
            'provider' => $provider,
            'endpoint' => 'https://mcp.example.com',
            'credentials' => ['access_token' => 'secret'],
        ], $overrides));
    }

    protected function tool(array $schema = ['type' => 'object']): McpTool
    {
        return new McpTool('send_email', 'Send an email', $schema);
    }

    #[Test] public function it_validates_required_arguments()
    {
        $connection = $this->makeConnection('gmail');
        $invoker = new McpToolInvoker($this->connections);

        $tool = $this->tool([
            'type' => 'object',
            'properties' => ['to' => ['type' => 'string']],
            'required' => ['to'],
        ]);

        $this->expectException(McpException::class);
        $this->expectExceptionMessage('Missing required field');

        $invoker->call($connection, $tool, []);
    }

    #[Test] public function it_validates_argument_types()
    {
        $connection = $this->makeConnection('gmail');
        $invoker = new McpToolInvoker($this->connections);

        $tool = $this->tool([
            'type' => 'object',
            'properties' => ['limit' => ['type' => 'integer']],
        ]);

        $this->expectException(McpException::class);
        $this->expectExceptionMessage('must be of type integer');

        $invoker->call($connection, $tool, ['limit' => 'not-an-int']);
    }

    #[Test] public function it_rate_limits_per_tenant_and_provider()
    {
        $connection = $this->makeConnection('rate_limited', [
            'rate_limit' => ['max_per_minute' => 2],
        ]);
        $invoker = new McpToolInvoker($this->connections);

        Http::fake(function ($request) {
            $method = $request->data()['method'] ?? '';
            $id = $request->data()['id'] ?? 0;
            $result = $method === 'initialize'
                ? ['serverInfo' => ['name' => 'demo']]
                : ['content' => [['type' => 'text', 'text' => 'ok']], 'isError' => false];

            return Http::response(['jsonrpc' => '2.0', 'id' => $id, 'result' => $result]);
        });

        $tool = $this->tool();

        $invoker->call($connection, $tool, []);
        $invoker->call($connection, $tool, []);

        $this->expectException(McpException::class);
        $this->expectExceptionMessage('rate limit');

        $invoker->call($connection, $tool, []);
    }

    #[Test] public function it_opens_circuit_after_consecutive_failures()
    {
        $connection = $this->makeConnection('circuit_test');
        $invoker = new McpToolInvoker($this->connections, maxAttempts: 1, circuitThreshold: 3);

        Http::fake([
            'https://mcp.example.com*' => Http::response('boom', 500),
        ]);

        $tool = $this->tool();

        for ($i = 0; $i < 3; $i++) {
            try {
                $invoker->call($connection, $tool, []);
            } catch (McpException $e) {
                // expected
            }
        }

        $connection->refresh();
        $this->assertNotNull($connection->circuit_open_until);
        $this->assertTrue($connection->circuit_open_until->isFuture());
        $this->assertSame(3, $connection->consecutive_failures);

        $this->expectException(McpException::class);
        $this->expectExceptionMessage('circuit');

        $invoker->call($connection, $tool, []);
    }

    #[Test] public function it_retries_transient_failures()
    {
        $connection = $this->makeConnection('retry_test');
        $invoker = new McpToolInvoker($this->connections);

        $calls = 0;
        Http::fake(function ($request) use (&$calls) {
            $method = $request->data()['method'] ?? '';
            $id = $request->data()['id'] ?? 0;

            if ($method === 'initialize') {
                return Http::response(['jsonrpc' => '2.0', 'id' => $id, 'result' => ['serverInfo' => ['name' => 'demo']]]);
            }

            $calls++;

            if ($calls === 1) {
                return Http::response('boom', 500);
            }

            return Http::response(['jsonrpc' => '2.0', 'id' => $id, 'result' => ['content' => [['type' => 'text', 'text' => 'ok']], 'isError' => false]]);
        });

        $tool = $this->tool();
        $result = $invoker->call($connection, $tool, []);

        $this->assertSame(2, $calls);
        $this->assertSame('ok', $result['content'][0]['text']);
    }

    #[Test] public function it_adds_mcp_metadata_to_handler_result()
    {
        $connection = $this->makeConnection('gmail');
        $invoker = new McpToolInvoker($this->connections);

        Http::fake(function ($request) {
            $method = $request->data()['method'] ?? '';
            $id = $request->data()['id'] ?? 0;
            $result = $method === 'initialize'
                ? ['serverInfo' => ['name' => 'demo']]
                : ['content' => [['type' => 'text', 'text' => 'ok']], 'isError' => false];

            return Http::response(['jsonrpc' => '2.0', 'id' => $id, 'result' => $result]);
        });

        $handler = new McpToolHandler(
            $this->tool(),
            $this->connections->buildClient($connection),
            invoker: $invoker,
            connection: $connection,
        );

        $result = $handler->execute(['to' => 'x@example.com']);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('mcp', $result);
        $this->assertSame($connection->id, $result['mcp']['connection_id']);
        $this->assertSame('gmail', $result['mcp']['provider']);
        $this->assertSame('send_email', $result['mcp']['tool']);
    }
}

