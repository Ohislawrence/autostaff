<?php

namespace App\Automation\Contracts;

interface ActionInterface
{
    /**
     * The unique identifier for this action type.
     */
    public function identifier(): string;

    /**
     * Human-readable label shown in the UI.
     */
    public function label(): string;

    /**
     * Description of what this action does.
     */
    public function description(): string;

    /**
     * Configuration schema for the action (JSON Schema format).
     */
    public function configSchema(): array;

    /**
     * Execute the action with the given configuration and trigger data.
     */
    public function execute(array $config, array $triggerData): array;
}