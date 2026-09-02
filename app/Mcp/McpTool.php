<?php

namespace App\Mcp;

/**
 * A tool advertised by an MCP server (from tools/list).
 */
class McpTool
{
    public function __construct(
        public readonly string $name,
        public readonly string $description = '',
        public readonly array $inputSchema = [],
    ) {}

    public static function fromListEntry(array $entry): self
    {
        return new self(
            name: (string) ($entry['name'] ?? ''),
            description: (string) ($entry['description'] ?? ''),
            inputSchema: $entry['inputSchema'] ?? [],
        );
    }
}
