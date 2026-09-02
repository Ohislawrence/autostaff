<?php

namespace App\Ai\Workflow;

use App\Models\Conversation;

/**
 * Conversation workflow state machine.
 *
 * The backend owns workflow truth: current state, allowed transitions, and the
 * next action. The LLM only reasons in natural language; a tool that would
 * produce an invalid transition is rejected here before it can affect data.
 *
 * State is persisted on `conversation.metadata.workflow` (no schema migration
 * required).
 */
class WorkflowStateMachine
{
    /**
     * Read-only tools that never mutate workflow state and are always allowed.
     */
    protected const READ_ONLY_TOOLS = [
        'search_products', 'get_product', 'get_price', 'check_inventory',
        'get_customer', 'get_order', 'get_order_status', 'get_available_slots',
        'knowledge_search', 'generate_report', 'extract_document_data',
        'send_followup',
    ];

    /**
     * Mutating tools with explicit preconditions.
     *
     * Each entry declares which source states permit the tool, and the target
     * state reached after a verified success.
     */
    protected const MUTATING_TOOLS = [
        'generate_quotation' => [
            'from' => ['idle', 'product_inquiry'],
            'to' => 'quotation_sent',
        ],
        'generate_invoice' => [
            'from' => ['quotation_sent', 'awaiting_payment'],
            'to' => 'awaiting_payment',
        ],
        'record_payment' => [
            'from' => ['awaiting_payment'],
            'to' => 'order_confirmed',
        ],
        'create_order' => [
            'from' => ['idle', 'product_inquiry', 'quotation_sent', 'awaiting_payment'],
            'to' => 'order_confirmed',
        ],
        'cancel_order' => [
            'from' => ['quotation_sent', 'awaiting_payment', 'order_confirmed'],
            'to' => 'cancelled',
        ],
        'schedule_appointment' => [
            'from' => ['idle'],
            'to' => 'appointment_scheduled',
        ],
    ];

    /**
     * Current workflow state for a conversation.
     *
     * @return array{workflow: string, state: string, updated_at: ?string}
     */
    public function current(Conversation $conversation): array
    {
        $meta = $conversation->metadata ?? [];
        $workflow = $meta['workflow'] ?? [];

        return [
            'workflow' => $workflow['workflow'] ?? 'sales',
            'state' => $workflow['state'] ?? 'idle',
            'updated_at' => $workflow['updated_at'] ?? null,
        ];
    }

    /**
     * Pre-check: is this tool permitted from the current state?
     * This performs NO mutation — state is only advanced after a verified success.
     */
    public function authorizeTool(Conversation $conversation, string $toolIdentifier): array
    {
        if (in_array($toolIdentifier, self::READ_ONLY_TOOLS, true)) {
            return ['allowed' => true, 'read_only' => true];
        }

        if (! isset(self::MUTATING_TOOLS[$toolIdentifier])) {
            // Unmanaged tool (e.g. create_lead, create_task, transfer_to_human).
            return ['allowed' => true];
        }

        $current = $this->current($conversation);
        $from = $current['state'];

        $definition = self::MUTATING_TOOLS[$toolIdentifier];
        if (in_array($from, $definition['from'], true)) {
            return ['allowed' => true, 'from' => $from, 'to' => $definition['to']];
        }

        return [
            'allowed' => false,
            'from' => $from,
            'to' => $definition['to'],
            'reason' => "Cannot perform '{$toolIdentifier}' from state '{$from}'.",
        ];
    }

    /**
     * Advance the workflow state after a tool has been successfully verified.
     */
    public function recordTransition(Conversation $conversation, string $toolIdentifier): void
    {
        if (! isset(self::MUTATING_TOOLS[$toolIdentifier])) {
            return;
        }

        $definition = self::MUTATING_TOOLS[$toolIdentifier];
        $current = $this->current($conversation);

        if (! in_array($current['state'], $definition['from'], true)) {
            return;
        }

        $this->setState($conversation, $definition['to'], $current['workflow']);
    }

    protected function setState(Conversation $conversation, string $state, string $workflow): void
    {
        $meta = $conversation->metadata ?? [];
        $meta['workflow'] = [
            'workflow' => $workflow,
            'state' => $state,
            'updated_at' => now()->toISOString(),
        ];

        $conversation->update(['metadata' => $meta]);
    }
}