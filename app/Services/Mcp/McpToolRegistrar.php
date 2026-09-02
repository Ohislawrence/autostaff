<?php

namespace App\Services\Mcp;

use App\Ai\Tools\ToolInterface;
use App\Mcp\McpTool;
use App\Mcp\McpToolHandler;
use App\Models\McpConnection as McpConnectionModel;
use App\Models\Tool;

/**
 * Syncs MCP tools into tenant-scoped Tool rows and resolves them back into
 * executable handlers at runtime.
 */
class McpToolRegistrar
{
    public function __construct(
        protected McpConnectionManager $connections,
        protected McpToolInvoker $invoker,
    ) {}

    public function sync(McpConnectionModel $connection): int
    {
        $entries = $this->connections->listTools($connection);
        $count = 0;

        foreach ($entries as $entry) {
            $mcpTool = McpTool::fromListEntry($entry);
            if ($mcpTool->name === '') {
                continue;
            }

            $identifier = $this->identifierFor($connection, $mcpTool->name);

            Tool::updateOrCreate(
                ['identifier' => $identifier],
                [
                    'name' => $this->sanitizeName($mcpTool->name),
                    'description' => $this->sanitizeDescription($mcpTool->description ?: 'MCP tool from ' . $connection->provider),
                    'input_schema' => $mcpTool->inputSchema ?: ['type' => 'object'],
                    'output_schema' => ['type' => 'object'],
                    'handler_class' => McpToolHandler::class,
                    'requires_confirmation' => false,
                    'category' => 'external',
                    'is_custom' => true,
                    'organization_id' => $connection->organization_id,
                    'custom_config' => [
                        'mcp_connection_id' => $connection->id,
                        'mcp_tool' => $mcpTool->name,
                    ],
                    'is_active' => true,
                ]
            );

            $count++;
        }

        return $count;
    }

    public function resolve(string $identifier, ?int $organizationId): ?ToolInterface
    {
        if (! config('mcp.enabled', true)) {
            return null;
        }

        if (! $organizationId) {
            return null;
        }

        $tool = Tool::query()
            ->where('organization_id', $organizationId)
            ->where('identifier', $identifier)
            ->where('is_custom', true)
            ->first();

        if (! $tool) {
            return null;
        }

        $config = $tool->custom_config ?? [];
        $connectionId = $config['mcp_connection_id'] ?? null;
        $mcpToolName = $config['mcp_tool'] ?? null;

        if (! $connectionId || ! $mcpToolName) {
            return null;
        }

        $connection = $this->connections->find($organizationId, (int) $connectionId);
        if (! $connection) {
            return null;
        }

        return new McpToolHandler(
            tool: new McpTool(
                name: $mcpToolName,
                description: $tool->description,
                inputSchema: $tool->input_schema ?? [],
            ),
            client: $this->connections->buildClient($connection),
            requiresConfirmation: (bool) $tool->requires_confirmation,
            category: $tool->category ?? 'external',
            identifier: $tool->identifier,
            invoker: $this->invoker,
            connection: $connection,
        );
    }

    public function removeForConnection(McpConnectionModel $connection): int
    {
        $tools = Tool::query()
            ->where('organization_id', $connection->organization_id)
            ->where('is_custom', true)
            ->get();

        $deleted = 0;

        foreach ($tools as $tool) {
            $config = $tool->custom_config ?? [];
            if (($config['mcp_connection_id'] ?? null) == $connection->id) {
                $tool->delete();
                $deleted++;
            }
        }

        return $deleted;
    }

    protected function sanitizeName(string $name): string
    {
        $name = preg_replace('/[\x00-\x1F\x7F]/', '', $name);

        return mb_substr(trim($name), 0, 100) ?: 'mcp_tool';
    }

    protected function sanitizeDescription(string $description): string
    {
        $description = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $description);

        return mb_substr(trim($description), 0, (int) config('mcp.max_description_length', 2000));
    }

    protected function identifierFor(McpConnectionModel $connection, string $toolName): string
    {
        $safe = strtolower(trim($toolName));
        $safe = preg_replace('/[^a-z0-9_]+/', '_', $safe);
        $safe = trim($safe, '_');
        $safe = mb_substr($safe ?: 'tool', 0, 40);

        return "mcp_{$connection->id}_{$safe}";
    }
}
