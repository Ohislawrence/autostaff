<?php

namespace App\Automation\Contracts;

interface ConditionInterface
{
    /**
     * The unique identifier for this condition type.
     */
    public function identifier(): string;

    /**
     * Human-readable label shown in the UI when building conditions.
     */
    public function label(): string;

    /**
     * Configuration schema for the condition (JSON Schema format).
     */
    public function configSchema(): array;

    /**
     * Evaluate the condition against trigger data.
     */
    public function evaluate(array $config, array $triggerData): bool;
}