<?php

namespace App\Ai\Tools;

use App\Models\Tool;
use Closure;

class ToolRegistry
{
    protected array $handlers = [];

    protected ?Closure $dynamicResolver = null;

    /**
     * Register a tool handler class.
     */
    public function register(string $identifier, string $handlerClass): void
    {
        $this->handlers[$identifier] = $handlerClass;
    }

    /**
     * Set a fallback resolver for dynamic (e.g. tenant MCP) tools.
     */
    public function setDynamicResolver(Closure $resolver): void
    {
        $this->dynamicResolver = $resolver;
    }

    /**
     * Get the handler instance for a tool identifier.
     */
    public function getHandler(string $identifier, ?int $organizationId = null): ?ToolInterface
    {
        if (isset($this->handlers[$identifier])) {
            return app($this->handlers[$identifier]);
        }

        if ($this->dynamicResolver) {
            return ($this->dynamicResolver)($identifier, $organizationId);
        }

        return null;
    }

    /**
     * Get all registered tool identifiers.
     */
    public function getIdentifiers(): array
    {
        return array_keys($this->handlers);
    }

    /**
     * Get all registered tools as handler instances.
     *
     * @return array<string, ToolInterface>
     */
    public function all(): array
    {
        $tools = [];
        foreach ($this->handlers as $identifier => $class) {
            $tools[$identifier] = app($class);
        }
        return $tools;
    }

    /**
     * Check if a tool is registered.
     */
    public function has(string $identifier): bool
    {
        return isset($this->handlers[$identifier]);
    }

    /**
     * Sync all registered tools to the database.
     * This ensures the tools table has entries for every registered tool.
     */
    public function syncToDatabase(): void
    {
        foreach ($this->all() as $identifier => $handler) {
            Tool::updateOrCreate(
                ['identifier' => $identifier],
                [
                    'name' => $handler->getName(),
                    'description' => $handler->getDescription(),
                    'input_schema' => $handler->getInputSchema(),
                    'output_schema' => $handler->getOutputSchema(),
                    'handler_class' => get_class($handler),
                    'requires_confirmation' => $handler->requiresConfirmation(),
                    'category' => $handler->getCategory(),
                    'is_active' => true,
                ]
            );
        }
    }
}