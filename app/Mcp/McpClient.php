<?php

namespace App\Mcp;

use App\Mcp\Transports\HttpStreamableTransport;
use App\Mcp\Transports\McpTransportInterface;
use App\Mcp\Transports\StdioTransport;

/**
 * Minimal MCP client (JSON-RPC 2.0).
 *
 * Implements the subset of the Model Context Protocol the platform needs:
 * initialize, tools/list, tools/call, and ping.
 */
class McpClient
{
    protected int $requestId = 0;

    protected bool $initialized = false;

    public function __construct(
        protected McpTransportInterface $transport,
        protected McpConnection $connection,
    ) {}

    public static function forConnection(McpConnection $connection): self
    {
        $transport = $connection->transport === 'stdio'
            ? new StdioTransport($connection)
            : new HttpStreamableTransport($connection);

        return new self($transport, $connection);
    }

    public function initialize(): array
    {
        $result = $this->request('initialize', [
            'protocolVersion' => $this->connection->protocolVersion,
            'capabilities' => (object) [],
            'clientInfo' => $this->connection->clientInfo,
        ]);

        $this->initialized = true;

        return $result;
    }

    /**
     * @return array<int, array> Raw tool entries from tools/list.
     */
    public function listTools(): array
    {
        $this->ensureInitialized();

        $result = $this->request('tools/list', []);

        return $result['tools'] ?? [];
    }

    /**
     * Call a tool by name and return the raw tools/call result.
     */
    public function callTool(string $name, array $arguments = []): array
    {
        $this->ensureInitialized();

        return $this->request('tools/call', [
            'name' => $name,
            'arguments' => $arguments,
        ]);
    }

    public function ping(): bool
    {
        $this->ensureInitialized();

        $this->request('ping', []);

        return true;
    }

    protected function ensureInitialized(): void
    {
        if (! $this->initialized) {
            $this->initialize();
        }
    }

    protected function request(string $method, array $params): array
    {
        $id = ++$this->requestId;

        $response = $this->transport->send([
            'jsonrpc' => '2.0',
            'id' => $id,
            'method' => $method,
            'params' => $params,
        ]);

        if (! is_array($response)) {
            throw new McpException('MCP transport returned a malformed response.');
        }

        if (array_key_exists('error', $response)) {
            $error = $response['error'];

            throw new McpException(
                (string) ($error['message'] ?? 'MCP error'),
                (int) ($error['code'] ?? 0),
                $error['data'] ?? null,
            );
        }

        return $response['result'] ?? [];
    }
}
