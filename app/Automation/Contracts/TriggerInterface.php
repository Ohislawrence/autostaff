<?php

namespace App\Automation\Contracts;

interface TriggerInterface
{
    /**
     * The unique identifier for this trigger type (stored in automations.trigger_type).
     */
    public function identifier(): string;

    /**
     * Human-readable label shown in the UI.
     */
    public function label(): string;

    /**
     * Description of what this trigger fires on.
     */
    public function description(): string;

    /**
     * Configuration schema for the trigger (JSON Schema format).
     */
    public function configSchema(): array;

    /**
     * Extract the standardized payload from the triggering event.
     * Returns the triggerData array passed to the AutomationService.
     */
    public function extractPayload(object|array $event): array;

    /**
     * The organization ID extracted from the event (used to scope automations).
     */
    public function extractOrganizationId(object|array $event): int;
}