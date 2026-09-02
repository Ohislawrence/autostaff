<?php

namespace App\Ai\Tools;

abstract class BaseTool implements ToolInterface
{
    protected string $identifier;
    protected string $name;
    protected string $description;
    protected string $category;
    protected bool $requiresConfirmation = false;

    abstract public function execute(array $parameters): array;

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getCategory(): string
    {
        return $this->category;
    }

    /**
     * Default output schema. Concrete tools may override with a more specific
     * schema; tools that don't declare one inherit this generic object schema.
     */
    public function getOutputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'success' => ['type' => 'boolean'],
                'message' => ['type' => 'string'],
            ],
        ];
    }

    public function requiresConfirmation(): bool
    {
        return $this->requiresConfirmation;
    }

    /**
     * Create a success response.
     */
    protected function success(string $message, array $data = []): array
    {
        return array_merge([
            'success' => true,
            'message' => $message,
        ], $data);
    }

    /**
     * Create an error response.
     */
    protected function error(string $message): array
    {
        return [
            'success' => false,
            'error' => $message,
        ];
    }
}