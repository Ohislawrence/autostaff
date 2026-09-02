<?php

namespace App\Ai\Tools;

interface ToolInterface
{
    /**
     * Unique identifier for this tool (e.g., 'search_products').
     */
    public function getIdentifier(): string;

    /**
     * Human-readable name.
     */
    public function getName(): string;

    /**
     * Description of what the tool does (for AI context).
     */
    public function getDescription(): string;

    /**
     * JSON Schema for input parameters.
     */
    public function getInputSchema(): array;

    /**
     * JSON Schema for output.
     */
    public function getOutputSchema(): array;

    /**
     * Execute the tool with given parameters.
     */
    public function execute(array $parameters): array;

    /**
     * Whether this tool requires human confirmation before execution.
     */
    public function requiresConfirmation(): bool;

    /**
     * Category this tool belongs to.
     */
    public function getCategory(): string;
}