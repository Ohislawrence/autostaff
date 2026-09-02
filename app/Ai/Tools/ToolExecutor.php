<?php

namespace App\Ai\Tools;

use App\Ai\Workflow\PolicyService;
use App\Ai\Workflow\WorkflowStateMachine;
use App\Models\AiEmployee;
use App\Models\Conversation;
use App\Models\ToolExecution;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ToolExecutor
{
    public function __construct(
        protected ToolRegistry $registry,
        protected ?WorkflowStateMachine $workflow = null,
        protected ?PolicyService $policy = null,
    ) {}

    /**
     * Execute a tool for an AI employee in a conversation context.
     *
     * Enforcement order:
     *   1. permission (is the tool allowed for this employee?)
     *   2. workflow (is the transition valid from the current state?) — DECIDE
     *   3. policy (does this need human approval?) — POLICY
     *   4. confirmation flag (tool-level or pivot-level)
     *   5. execute + verify result
     */
    public function execute(
        AiEmployee $employee,
        Conversation $conversation,
        string $toolIdentifier,
        array $parameters,
        ?string $correlationId = null,
    ): array {
        // 1. Permission
        $permission = $employee->tools()
            ->where('identifier', $toolIdentifier)
            ->wherePivot('is_allowed', true)
            ->first();

        if (! $permission) {
            return $this->errorResult('Tool not permitted for this employee.');
        }

        $handler = $this->registry->getHandler($toolIdentifier, $employee->organization_id);
        if (! $handler) {
            return $this->errorResult("Tool '{$toolIdentifier}' not found.");
        }

        // 2. Workflow state gate (DECIDE)
        if ($this->workflow) {
            $workflowDecision = $this->workflow->authorizeTool($conversation, $toolIdentifier);
            if (! $workflowDecision['allowed']) {
                return $this->deniedResult($workflowDecision['reason'] ?? "Tool '{$toolIdentifier}' is not allowed from the current workflow state.");
            }
        }

        // 3. Policy gate (POLICY)
        $policyRequiresApproval = false;
        if ($this->policy) {
            $policyDecision = $this->policy->evaluate($employee, $conversation, $toolIdentifier, $parameters);
            if (! $policyDecision['allowed'] && $policyDecision['requires_approval']) {
                $policyRequiresApproval = true;
            }
        }

        $needsConfirmation = $policyRequiresApproval
            || $handler->requiresConfirmation()
            || $permission->pivot->requires_confirmation;

        // Create tool execution record
        $execution = ToolExecution::create([
            'organization_id' => $employee->organization_id,
            'tool_id' => $permission->id,
            'ai_employee_id' => $employee->id,
            'conversation_id' => $conversation->id,
            'input_parameters' => $parameters,
            'status' => $needsConfirmation ? 'pending' : 'executing',
            'requires_confirmation' => $needsConfirmation,
        ]);

        // If confirmation required, return pending status
        if ($needsConfirmation) {
            return [
                'status' => 'pending_confirmation',
                'message' => "Action '{$handler->getName()}' requires confirmation.",
                'tool' => $toolIdentifier,
                'parameters' => $parameters,
                'execution_id' => $execution->id,
            ];
        }

        return $this->runExecution($execution, $handler, $parameters, $employee, $conversation);
    }

    /**
     * Confirm and execute a pending tool execution.
     */
    public function confirm(int $executionId, int $userId): array
    {
        $execution = ToolExecution::with(['aiEmployee', 'conversation'])->findOrFail($executionId);

        if ($execution->status !== 'pending') {
            return $this->errorResult('This execution is not pending confirmation.');
        }

        $handler = $this->registry->getHandler(
            $execution->tool->identifier,
            $execution->organization_id
        );

        if (! $handler) {
            return $this->errorResult('Tool handler not found.');
        }

        $execution->update([
            'was_confirmed' => true,
            'confirmed_by' => $userId,
            'confirmed_at' => now(),
        ]);

        return $this->runExecution($execution, $handler, $execution->input_parameters, $execution->aiEmployee, $execution->conversation);
    }

    /**
     * Run the actual tool execution.
     */
    protected function runExecution(
        ToolExecution $execution,
        ToolInterface $handler,
        array $parameters,
        AiEmployee $employee,
        Conversation $conversation
    ): array {
        $execution->update(['status' => 'executing']);
        $startTime = microtime(true);

        try {
            // Add employee and conversation context to parameters for tools that need it
            $parameters['_employee'] = $employee;
            $parameters['_conversation'] = $conversation;

            $result = $handler->execute($parameters);
            $executionTimeMs = (int) ((microtime(true) - $startTime) * 1000);

            $verified = (bool) ($result['success'] ?? true);
            $status = $verified ? 'success' : 'failed';

            $execution->update([
                'status' => $status,
                'output_result' => $result,
                'execution_time_ms' => $executionTimeMs,
                'estimated_cost' => $result['estimated_cost'] ?? 0,
                'error_message' => $result['error'] ?? null,
            ]);

            // Only advance workflow state when the tool actually verified success.
            if ($verified && $this->workflow) {
                $this->workflow->recordTransition($conversation, $handler->getIdentifier());
            }

            return array_merge(['status' => $status, 'verified' => $verified, 'execution_time_ms' => $executionTimeMs], $result);

        } catch (\Exception $e) {
            Log::error("Tool execution failed: {$handler->getIdentifier()}", [
                'error' => $e->getMessage(),
                'parameters' => $parameters,
            ]);

            $execution->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            return $this->errorResult("Tool execution failed: {$e->getMessage()}");
        }
    }

    protected function errorResult(string $message): array
    {
        return [
            'status' => 'error',
            'verified' => false,
            'error' => $message,
        ];
    }

    protected function deniedResult(string $message): array
    {
        return [
            'status' => 'denied',
            'verified' => false,
            'error' => $message,
        ];
    }
}