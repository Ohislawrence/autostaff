<?php

namespace App\Mcp;

use App\Ai\Tools\BaseTool;
use App\Models\McpConnection as McpConnectionModel;
use App\Services\Mcp\McpToolInvoker;

/**
 * Adapts an MCP tool so it can flow through the platform's native ToolInterface
 * (and therefore ToolExecutor) exactly like a built-in Laravel tool.
 */
class McpToolHandler extends BaseTool
{
    public function __construct(
        protected McpTool $tool,
        protected McpClient $client,
        bool $requiresConfirmation = false,
        string $category = 'external',
        ?string $identifier = null,
        protected ?McpToolInvoker $invoker = null,
        protected ?McpConnectionModel $connection = null,
    ) {
        $this->identifier = $identifier ?? $tool->name;
        $this->name = $tool->name;
        $this->description = $tool->description;
        $this->category = $category;
        $this->requiresConfirmation = $requiresConfirmation;
    }

    public function getInputSchema(): array
    {
        return $this->tool->inputSchema ?: ['type' => 'object'];
    }

    public function execute(array $parameters): array
    {
        // Strip framework-injected context keys before sending to the MCP server.
        unset($parameters['_employee'], $parameters['_conversation']);

        try {
            $result = $this->invoke($parameters);

            $isError = (bool) ($result['isError'] ?? false);
            $text = $this->extractText($result['content'] ?? []);

            return [
                'success' => ! $isError,
                'message' => $text !== '' ? $text : ($isError ? 'MCP tool returned an error.' : 'MCP tool executed successfully.'),
                'data' => $result,
                'estimated_cost' => $this->resolveCost($result),
                'mcp' => [
                    'connection_id' => $this->connection?->id,
                    'provider' => $this->connection?->provider,
                    'tool' => $this->tool->name,
                ],
            ];
        } catch (McpException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    protected function invoke(array $parameters): array
    {
        if ($this->invoker && $this->connection) {
            return $this->invoker->call($this->connection, $this->tool, $parameters);
        }

        return $this->client->callTool($this->tool->name, $parameters);
    }

    protected function resolveCost(array $result): float
    {
        foreach (['cost', 'price', 'estimated_cost'] as $key) {
            if (isset($result[$key]) && is_numeric($result[$key])) {
                return (float) $result[$key];
            }
        }

        if (isset($result['meta']['cost']) && is_numeric($result['meta']['cost'])) {
            return (float) $result['meta']['cost'];
        }

        $pricing = $this->connection?->pricing ?? [];

        return (float) ($pricing['per_call'] ?? 0);
    }

    protected function extractText(array $content): string
    {
        $parts = [];

        foreach ($content as $item) {
            if (($item['type'] ?? '') === 'text' && isset($item['text'])) {
                $parts[] = (string) $item['text'];
            }
        }

        return implode("\n", $parts);
    }
}
