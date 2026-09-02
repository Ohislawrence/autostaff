<?php

namespace Tests\Feature;

use App\Mcp\McpConnection;
use App\Mcp\McpException;
use App\Mcp\Transports\HttpStreamableTransport;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class McpHttpTransportTest extends TestCase
{
    #[Test] public function it_sends_json_rpc_and_decodes_json_response()
    {
        Http::fake([
            'https://mcp.example/*' => Http::response(
                ['jsonrpc' => '2.0', 'id' => 1, 'result' => ['serverInfo' => ['name' => 'demo']]],
                200,
                ['Content-Type' => 'application/json'],
            ),
        ]);

        $transport = new HttpStreamableTransport(McpConnection::http('https://mcp.example/mcp'));

        $response = $transport->send([
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'initialize',
            'params' => [],
        ]);

        $this->assertSame('demo', $response['result']['serverInfo']['name']);

        Http::assertSent(fn ($request) => $request->url() === 'https://mcp.example/mcp');
    }

    #[Test] public function it_parses_sse_responses()
    {
        $body = "data: {\"jsonrpc\":\"2.0\",\"id\":1,\"result\":{\"ok\":true}}\n\n";

        Http::fake([
            'https://mcp.example/*' => Http::response($body, 200, ['Content-Type' => 'text/event-stream']),
        ]);

        $transport = new HttpStreamableTransport(McpConnection::http('https://mcp.example/mcp'));

        $response = $transport->send([
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'ping',
            'params' => [],
        ]);

        $this->assertTrue($response['result']['ok']);
    }

    #[Test] public function it_throws_on_http_error_status()
    {
        Http::fake([
            'https://mcp.example/*' => Http::response('boom', 500),
        ]);

        $transport = new HttpStreamableTransport(McpConnection::http('https://mcp.example/mcp'));

        $this->expectException(McpException::class);
        $this->expectExceptionMessage('500');

        $transport->send([
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'ping',
            'params' => [],
        ]);
    }
}
