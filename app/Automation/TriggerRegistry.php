<?php

namespace App\Automation;

use App\Automation\Contracts\TriggerInterface;
use App\Automation\Triggers\LeadStageChangedTrigger;
use App\Automation\Triggers\NewLeadTrigger;
use App\Automation\Triggers\NewMessageTrigger;
use App\Automation\Triggers\OrderCreatedTrigger;
use Illuminate\Support\Collection;

class TriggerRegistry
{
    /**
     * Registered triggers keyed by identifier.
     */
    protected array $triggers = [];

    /**
     * The registered trigger class names for auto-discovery.
     */
    protected array $triggerClasses = [
        NewMessageTrigger::class,
        NewLeadTrigger::class,
        OrderCreatedTrigger::class,
        LeadStageChangedTrigger::class,
    ];

    /**
     * Resolve and register all triggers.
     */
    public function boot(): void
    {
        foreach ($this->triggerClasses as $class) {
            $trigger = app($class);
            $this->triggers[$trigger->identifier()] = $trigger;
        }
    }

    /**
     * Get a specific trigger by identifier.
     */
    public function get(string $identifier): ?TriggerInterface
    {
        return $this->triggers[$identifier] ?? null;
    }

    /**
     * Get all registered triggers.
     *
     * @return Collection<string, TriggerInterface>
     */
    public function all(): Collection
    {
        return collect($this->triggers);
    }

    /**
     * Get all triggers as an array suitable for the UI.
     */
    public function toArray(): array
    {
        return collect($this->triggers)->map(fn (TriggerInterface $t) => [
            'id' => $t->identifier(),
            'label' => $t->label(),
            'description' => $t->description(),
            'configSchema' => $t->configSchema(),
        ])->values()->toArray();
    }

    /**
     * Register an additional trigger at runtime.
     */
    public function register(TriggerInterface $trigger): void
    {
        $this->triggers[$trigger->identifier()] = $trigger;
    }

    /**
     * Get all registered trigger identifiers.
     */
    public function identifiers(): array
    {
        return array_keys($this->triggers);
    }
}