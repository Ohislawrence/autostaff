<?php

namespace App\Http\Controllers;

use App\Automation\ActionRegistry;
use App\Automation\TriggerRegistry;
use App\Models\Automation;
use App\Services\Automation\AutomationService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AutomationController extends Controller
{
    public function __construct(
        protected AutomationService $automationService,
        protected TriggerRegistry $triggerRegistry,
        protected ActionRegistry $actionRegistry,
    ) {}

    public function index()
    {
        $organization = current_org();
        $automations = $organization->automations()->withCount('runs')->latest()->get();
        return Inertia::render('Automations/Index', [
            'automations' => $automations,
            'triggers' => $this->getAvailableTriggers(),
            'actions' => $this->getAvailableActions(),
        ]);
    }

    public function store(Request $request)
    {
        $organization = current_org();
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'trigger_type' => 'required|string|in:new_lead,new_message,conversation_closed,order_created,payment_received,appointment_created,scheduled_time,customer_inactive',
            'trigger_config' => 'nullable|array',
            'conditions' => 'nullable|array',
            'actions' => 'required|array',
            'max_executions_per_day' => 'nullable|integer|min:1',
        ]);
        $organization->automations()->create(array_merge($validated, ['is_active' => true]));
        return back()->with('success', 'Automation created.');
    }

    public function update(Request $request, Automation $automation)
    {
        $orgId = current_org_id();
        if ($automation->organization_id !== $orgId) abort(403);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'trigger_type' => 'required|string',
            'trigger_config' => 'nullable|array',
            'conditions' => 'nullable|array',
            'actions' => 'required|array',
            'max_executions_per_day' => 'nullable|integer|min:1',
            'is_active' => 'nullable|boolean',
        ]);

        $automation->update($validated);

        return back()->with('success', 'Automation updated.');
    }

    public function toggle(Automation $automation)
    {
        $orgId = current_org_id(); if ($automation->organization_id !== $orgId) abort(403);
        $automation->update(['is_active' => ! $automation->is_active]);
        return back()->with('success', $automation->is_active ? 'Automation activated.' : 'Automation deactivated.');
    }

    public function destroy(Automation $automation)
    {
        $orgId = current_org_id(); if ($automation->organization_id !== $orgId) abort(403);
        $automation->delete();
        return back()->with('success', 'Automation deleted.');
    }

    protected function getAvailableTriggers(): array
    {
        // Pull from the registered TriggerRegistry for dynamic trigger discovery
        $registryTriggers = $this->triggerRegistry->toArray();

        $icons = [
            'new_lead' => '🎯',
            'new_message' => '💬',
            'lead_stage_changed' => '🔄',
            'order_created' => '📦',
            'payment_received' => '💰',
            'appointment_created' => '📅',
            'conversation_closed' => '✅',
            'scheduled_time' => '⏰',
        ];

        return array_map(fn ($t) => [
            'id' => $t['id'],
            'name' => $t['label'],
            'icon' => $icons[$t['id']] ?? '⚡',
            'description' => $t['description'],
        ], $registryTriggers);
    }

    protected function getAvailableActions(): array
    {
        // Pull from the registered ActionRegistry for dynamic action discovery
        $registryActions = $this->actionRegistry->toArray();

        // Also include legacy inline actions for backward compatibility
        $legacyActions = [
            ['id' => 'create_task', 'name' => 'Create Task', 'description' => 'Create a task for the team'],
            ['id' => 'notify_user', 'name' => 'Notify User', 'description' => 'Send an in-app notification'],
            ['id' => 'update_lead', 'name' => 'Update Lead', 'description' => 'Change lead stage'],
            ['id' => 'assign_conversation', 'name' => 'Assign Conversation', 'description' => 'Assign to a team member'],
            ['id' => 'create_webhook', 'name' => 'Call Webhook', 'description' => 'Send data to an external URL'],
            ['id' => 'send_email', 'name' => 'Send Email', 'description' => 'Send an email notification'],
        ];

        $actions = array_map(fn ($a) => [
            'id' => $a['id'],
            'name' => $a['label'],
            'description' => $a['description'],
        ], $registryActions);

        return array_merge($actions, $legacyActions);
    }
}