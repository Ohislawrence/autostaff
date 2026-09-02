<?php

namespace Tests\Feature;

use App\Mcp\McpClient;
use App\Mcp\McpConnection;
use App\Mcp\McpException;
use App\Mcp\McpTool;
use App\Mcp\McpToolHandler;
use App\Mcp\Transports\McpTransportInterface;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class McpClientTest extends TestCase
{
    protected function makeTransport(array &$responses, array &$requests): McpTransportInterface
    {
        return new class($responses, $requests) implements McpTransportInterface {
            private array $responses;
            private array $requests;

            public function __construct(array &$responses, array &$requests)
            {
                $this->responses = &$responses;
                $this->requests = &$requests;
            }

            public function send(array $request): array
            {
                $this->requests[] = $request;

                return array_shift($this->responses) ?? [];
            }
        };
    }

    #[Test] public function it_initializes_before_listing_tools()
    {
        $responses = [
            ['jsonrpc' => '2.0', 'id' => 1, 'result' => ['serverInfo' => ['name' => 'test-server']]],
            ['jsonrpc' => '2.0', 'id' => 2, 'result' => ['tools' => [['name' => 'search', 'description' => 'Search']]]],
        ];
        $requests = [];
        $transport = $this->makeTransport($responses, $requests);
        $client = new McpClient($transport, McpConnection::http('https://example.com/mcp'));

        $tools = $client->listTools();

        $this->assertCount(2, $requests);
        $this->assertSame('initialize', $requests[0]['method']);
        $this->assertSame('tools/list', $requests[1]['method']);
        $this->assertCount(1, $tools);
        $this->assertSame('search', $tools[0]['name']);
    }

    #[Test] public function it_does_not_reinitialize_when_already_initialized()
    {
        $responses = [
            ['jsonrpc' => '2.0', 'id' => 1, 'result' => []],
            ['jsonrpc' => '2.0', 'id' => 2, 'result' => ['tools' => []]],
            ['jsonrpc' => '2.0', 'id' => 3, 'result' => ['tools' => []]],
        ];
        $requests = [];
        $transport = $this->makeTransport($responses, $requests);
        $client = new McpClient($transport, McpConnection::http('https://example.com/mcp'));

        $client->initialize();
        $client->listTools();
        $client->listTools();

        $this->assertCount(3, $requests);
        $this->assertSame('initialize', $requests[0]['method']);
        $this->assertSame('tools/list', $requests[1]['method']);
        $this->assertSame('tools/list', $requests[2]['method']);
    }

    #[Test] public function it_throws_mcp_exception_on_rpc_error()
    {
        $responses = [
            ['jsonrpc' => '2.0', 'id' => 1, 'error' => ['code' => -32601, 'message' => 'Method not found']],
        ];
        $requests = [];
        $transport = $this->makeTransport($responses, $requests);
        $client = new McpClient($transport, McpConnection::http('https://example.com/mcp'));

        $this->expectException(McpException::class);
        $this->expectExceptionMessage('Method not found');

        $client->initialize();
    }

    #[Test] public function it_adapts_a_tool_through_the_handler()
    {
        $responses = [
            ['jsonrpc' => '2.0', 'id' => 1, 'result' => []],
            ['jsonrpc' => '2.0', 'id' => 2, 'result' => [
                'content' => [['type' => 'text', 'text' => 'Order 42 is confirmed.']],
                'isError' => false,
            ]],
        ];
        $requests = [];
        $transport = $this->makeTransport($responses, $requests);
        $client = new McpClient($transport, McpConnection::http('https://example.com/mcp'));

        $handler = new McpToolHandler(
            new McpTool('confirm_order', 'Confirm an order', ['type' => 'object']),
            $client,
        );

        $this->assertSame('confirm_order', $handler->getIdentifier());
        $this->assertSame('Confirm an order', $handler->getDescription());
        $this->assertSame(['type' => 'object'], $handler->getInputSchema());

        $result = $handler->execute(['order_id' => 42]);

        $this->assertTrue($result['success']);
        $this->assertSame('Order 42 is confirmed.', $result['message']);
        $this->assertSame('tools/call', $requests[1]['method']);
        $this->assertSame('confirm_order', $requests[1]['params']['name']);
        $this->assertSame(42, $requests[1]['params']['arguments']['order_id']);
    }

    #[Test] public function it_returns_error_result_when_tool_reports_error()
    {
        $responses = [
            ['jsonrpc' => '2.0', 'id' => 1, 'result' => []],
            ['jsonrpc' => '2.0', 'id' => 2, 'result' => [
                'content' => [['type' => 'text', 'text' => 'Not found']],
                'isError' => true,
            ]],
        ];
        $requests = [];
        $transport = $this->makeTransport($responses, $requests);
        $client = new McpClient($transport, McpConnection::http('https://example.com/mcp'));

        $handler = new McpToolHandler(new McpTool('lookup', 'Lookup'), $client);

        $result = $handler->execute([]);

        $this->assertFalse($result['success']);
        $this->assertSame('Not found', $result['message']);
    }
}
