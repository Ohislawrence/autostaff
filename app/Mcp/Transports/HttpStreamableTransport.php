<?php

namespace App\Mcp\Transports;

use App\Mcp\McpConnection;
use App\Mcp\McpException;
use Illuminate\Support\Facades\Http;

/**
 * JSON-RPC over MCP "Streamable HTTP" transport.
 *
 * Handles both plain JSON responses and Server-Sent Events (SSE).
 */
class HttpStreamableTransport implements McpTransportInterface
{
    public function __construct(protected McpConnection $connection) {}

    public function send(array $request): array
    {
        $response = Http::withHeaders($this->headers())
            ->accept('application/json, text/event-stream')
            ->timeout($this->connection->timeout)
            ->post($this->connection->endpoint, $request);

        if ($response->failed()) {
            throw new McpException(
                "MCP HTTP request failed with status {$response->status()}: " . mb_substr($response->body(), 0, 500)
            );
        }

        $contentType = strtolower($response->header('Content-Type') ?? '');

        if (str_contains($contentType, 'text/event-stream')) {
            return $this->parseSse($response->body());
        }

        $body = $response->json();
        if (! is_array($body)) {
            throw new McpException('MCP HTTP response was not valid JSON.');
        }

        return $body;
    }

    protected function headers(): array
    {
        return array_merge([
            'Content-Type' => 'application/json',
        ], $this->connection->headers);
    }

    protected function parseSse(string $body): array
    {
        foreach (preg_split('/\r?\n/', $body) as $line) {
            if (! str_starts_with($line, 'data:')) {
                continue;
            }

            $payload = trim(substr($line, 5));
            if ($payload === '') {
                continue;
            }

            $decoded = json_decode($payload, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        throw new McpException('MCP HTTP response contained no parseable SSE event.');
    }
}
