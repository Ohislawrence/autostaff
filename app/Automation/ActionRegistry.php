<?php

namespace App\Automation;

use App\Automation\Actions\InvokeAiEmployeeAction;
use App\Automation\Actions\SendMessageAction;
use App\Automation\Contracts\ActionInterface;
use Illuminate\Support\Collection;

class ActionRegistry
{
    /**
     * Registered actions keyed by identifier.
     */
    protected array $actions = [];

    /**
     * The registered action class names for auto-discovery.
     */
    protected array $actionClasses = [
        SendMessageAction::class,
        InvokeAiEmployeeAction::class,
    ];

    /**
     * Resolve and register all actions.
     */
    public function boot(): void
    {
        foreach ($this->actionClasses as $class) {
            $action = app($class);
            $this->actions[$action->identifier()] = $action;
        }
    }

    /**
     * Get a specific action by identifier.
     */
    public function get(string $identifier): ?ActionInterface
    {
        return $this->actions[$identifier] ?? null;
    }

    /**
     * Get all registered actions.
     *
     * @return Collection<string, ActionInterface>
     */
    public function all(): Collection
    {
        return collect($this->actions);
    }

    /**
     * Get all actions as an array suitable for the UI.
     */
    public function toArray(): array
    {
        return collect($this->actions)->map(fn (ActionInterface $a) => [
            'id' => $a->identifier(),
            'label' => $a->label(),
            'description' => $a->description(),
            'configSchema' => $a->configSchema(),
        ])->values()->toArray();
    }

    /**
     * Register an additional action at runtime.
     */
    public function register(ActionInterface $action): void
    {
        $this->actions[$action->identifier()] = $action;
    }

    /**
     * Execute an action by its identifier.
     */
    public function execute(string $identifier, array $config, array $triggerData): array
    {
        $action = $this->get($identifier);

        if (! $action) {
            return ['success' => false, 'error' => "Unknown action type: {$identifier}"];
        }

        return $action->execute($config, $triggerData);
    }

    /**
     * Get all registered action identifiers.
     */
    public function identifiers(): array
    {
        return array_keys($this->actions);
    }
}