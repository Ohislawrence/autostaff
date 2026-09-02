<?php

namespace App\Mcp;

/**
 * Value object describing how to reach an MCP server.
 *
 * For Phase 2 this is transport-only configuration. Per-tenant credentials,
 * persistence, and connection management are layered on in later phases.
 */
class McpConnection
{
    public function __construct(
        public readonly string $transport = 'http',
        public readonly ?string $endpoint = null,
        public readonly array $headers = [],
        public readonly string $protocolVersion = '2024-11-05',
        public readonly array $clientInfo = ['name' => 'nomdal', 'version' => '1.0.0'],
        public readonly int $timeout = 30,
        public readonly array $command = [],
        public readonly array $env = [],
    ) {}

    public static function http(string $endpoint, array $headers = [], int $timeout = 30): self
    {
        return new self(
            transport: 'http',
            endpoint: $endpoint,
            headers: $headers,
            timeout: $timeout,
        );
    }

    public static function stdio(array $command, array $env = [], int $timeout = 30): self
    {
        return new self(
            transport: 'stdio',
            command: $command,
            env: $env,
            timeout: $timeout,
        );
    }
}
