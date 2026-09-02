<?php

namespace App\Mcp;

/**
 * Thrown when an MCP transport or JSON-RPC interaction fails.
 */
class McpException extends \RuntimeException
{
    public function __construct(
        string $message,
        int $code = 0,
        public readonly ?array $data = null,
    ) {
        parent::__construct($message, $code);
    }
}
