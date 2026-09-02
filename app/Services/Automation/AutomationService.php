<?php

namespace App\Services\Automation;

use App\Automation\ActionRegistry;
use App\Automation\TriggerRegistry;
use App\Models\Automation;
use App\Models\AutomationRun;
use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\Message;
use App\Models\Order;
use App\Models\Task;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class AutomationService
{
    /**
     * Maximum nesting depth for automation-triggered automations.
     * Prevents infinite loops when an automation action (e.g. invoking an
     * AI employee) creates an event that triggers another automation.
     */
    protected const MAX_WORKFLOW_DEPTH = 3;

    /**
     * Evaluate and execute automations for a given trigger type.
     */
    public function process(string $triggerType, array $triggerData, int $organizationId): array
    {
        $results = [];

        $automations = Automation::where('organization_id', $organizationId)
            ->where('trigger_type', $triggerType)
            ->where('is_active', true)
            ->get();

        foreach ($automations as $automation) {
            $result = $this->execute($automation, $triggerData);
            $results[] = $result;
        }

        return $results;
    }

    /**
     * Execute a single automation.
     */
    public function execute(Automation $automation, array $triggerData): array
    {
        $executionId = 'auto_' . Str::uuid();
        $depth = $this->currentWorkflowDepth();

        if ($depth >= self::MAX_WORKFLOW_DEPTH) {
            Log::warning('Automation execution skipped: maximum workflow depth reached', [
                'automation_id' => $automation->id,
                'workflow_depth' => $depth,
            ]);

            return [
                'status' => 'depth_exceeded',
                'automation_id' => $automation->id,
                'workflow_depth' => $depth,
            ];
        }

        $workflowDepth = $depth + 1;
        app()->instance('automation_workflow_depth', $workflowDepth);

        try {
            app()->instance('current_organization_id', $automation->organization_id);

            // Check rate limits
            if (! $this->checkRateLimit($automation)) {
                return ['status' => 'rate_limited', 'automation_id' => $automation->id];
            }

            // Evaluate conditions
            $conditions = $automation->conditions ?? [];
            $conditionResults = $this->evaluateConditions($conditions, $triggerData);

            if (! $conditionResults['passed']) {
                return [
                    'status' => 'conditions_failed',
                    'automation_id' => $automation->id,
                    'condition_results' => $conditionResults,
                ];
            }

            // Execute actions
            $actionResults = $this->executeActions($automation->actions ?? [], $triggerData);

            // Log the run
            AutomationRun::create([
                'organization_id' => $automation->organization_id,
                'automation_id' => $automation->id,
                'execution_id' => $executionId,
                'status' => 'completed',
                'trigger_data' => $triggerData,
                'condition_results' => $conditionResults,
                'action_results' => $actionResults,
                'workflow_depth' => $workflowDepth,
            ]);

            $automation->update([
                'execution_count_today' => ($automation->execution_count_today ?? 0) + 1,
                'last_executed_at' => now(),
            ]);

            return [
                'status' => 'completed',
                'automation_id' => $automation->id,
                'actions_executed' => count($actionResults),
                'workflow_depth' => $workflowDepth,
            ];

        } catch (\Exception $e) {
            Log::error('Automation execution failed', [
                'automation_id' => $automation->id,
                'error' => $e->getMessage(),
            ]);

            AutomationRun::create([
                'organization_id' => $automation->organization_id,
                'automation_id' => $automation->id,
                'execution_id' => $executionId,
                'status' => 'failed',
                'trigger_data' => $triggerData,
                'error_message' => $e->getMessage(),
                'workflow_depth' => $workflowDepth,
            ]);

            return ['status' => 'failed', 'automation_id' => $automation->id, 'error' => $e->getMessage()];
        } finally {
            app()->instance('automation_workflow_depth', $depth);
        }
    }

    /**
     * Get the current automation workflow nesting depth.
     */
    protected function currentWorkflowDepth(): int
    {
        return app()->bound('automation_workflow_depth')
            ? (int) app('automation_workflow_depth')
            : 0;
    }

    /**
     * Evaluate conditions against trigger data.
     */
    protected function evaluateConditions(array $conditions, array $triggerData): array
    {
        if (empty($conditions)) {
            return ['passed' => true, 'results' => []];
        }

        $results = [];
        $allPassed = true;

        foreach ($conditions as $condition) {
            $field = $condition['field'] ?? '';
            $operator = $condition['operator'] ?? 'equals';
            $value = $condition['value'] ?? null;

            $actualValue = data_get($triggerData, $field);
            $passed = $this->evaluateCondition($actualValue, $operator, $value);

            $results[] = [
                'field' => $field,
                'operator' => $operator,
                'expected' => $value,
                'actual' => $actualValue,
                'passed' => $passed,
            ];

            if (! $passed) {
                $allPassed = false;
                if (($condition['logic'] ?? 'and') === 'and') {
                    break;
                }
            }
        }

        return ['passed' => $allPassed, 'results' => $results];
    }

    protected function evaluateCondition($actual, string $operator, $value): bool
    {
        return match ($operator) {
            'equals' => $actual == $value,
            'not_equals' => $actual != $value,
            'greater_than' => (float) $actual > (float) $value,
            'less_than' => (float) $actual < (float) $value,
            'contains' => is_string($actual) && str_contains(strtolower($actual), strtolower((string) $value)),
            'in' => in_array($actual, (array) $value),
            'not_in' => ! in_array($actual, (array) $value),
            'exists' => ! is_null($actual),
            'not_exists' => is_null($actual),
            default => false,
        };
    }

    /**
     * Execute the configured actions.
     */
    protected function executeActions(array $actions, array $triggerData): array
    {
        $results = [];

        foreach ($actions as $action) {
            $type = $action['type'] ?? '';
            $config = $action['config'] ?? [];

            $result = $this->executeSingleAction($type, $config, $triggerData);
            $results[] = ['type' => $type, 'result' => $result];
        }

        return $results;
    }

    protected function executeSingleAction(string $type, array $config, array $triggerData): array
    {
        // First try the ActionRegistry for new-style actions (including invoke_ai_employee)
        $actionRegistry = app(ActionRegistry::class);
        if (in_array($type, $actionRegistry->identifiers())) {
            return $actionRegistry->execute($type, $config, $triggerData);
        }

        // Fall back to inline implementations for backward compatibility
        return match ($type) {
            'send_message' => $this->actionSendMessage($config, $triggerData),
            'create_task' => $this->actionCreateTask($config, $triggerData),
            'notify_user' => $this->actionNotifyUser($config, $triggerData),
            'update_lead' => $this->actionUpdateLead($config, $triggerData),
            'assign_conversation' => $this->actionAssignConversation($config, $triggerData),
            'create_webhook' => $this->actionCreateWebhook($config, $triggerData),
            'send_email' => $this->actionSendEmail($config, $triggerData),
            default => ['success' => false, 'error' => "Unknown action type: {$type}"],
        };
    }

    protected function actionSendMessage(array $config, array $triggerData): array
    {
        $customerId = $triggerData['customer_id'] ?? $config['customer_id'] ?? null;
        $conversationId = $triggerData['conversation_id'] ?? null;
        $message = $this->renderTemplate($config['message'] ?? '', $triggerData);

        if ($conversationId) {
            $conversation = Conversation::find($conversationId);
            if ($conversation) {
                $conversation->messages()->create([
                    'organization_id' => $conversation->organization_id,
                    'conversation_id' => $conversation->id,
                    'type' => 'outgoing',
                    'content' => $message,
                    'channel' => $conversation->channel,
                ]);
                return ['success' => true, 'action' => 'Message sent'];
            }
        }

        return ['success' => false, 'error' => 'No conversation to send message to'];
    }

    protected function actionCreateTask(array $config, array $triggerData): array
    {
        $orgId = app('current_organization_id');
        $title = $this->renderTemplate($config['title'] ?? 'Automated Task', $triggerData);

        Task::create([
            'organization_id' => $orgId,
            'title' => $title,
            'description' => $this->renderTemplate($config['description'] ?? '', $triggerData) ?: null,
            'priority' => $config['priority'] ?? 'normal',
            'status' => 'open',
        ]);

        return ['success' => true, 'action' => 'Task created'];
    }

    protected function actionNotifyUser(array $config, array $triggerData): array
    {
        // Create an in-app notification
        $orgId = app('current_organization_id');

        \App\Models\Notification::create([
            'organization_id' => $orgId,
            'type' => 'in_app',
            'title' => $this->renderTemplate($config['title'] ?? 'Automation Notification', $triggerData),
            'body' => $this->renderTemplate($config['body'] ?? '', $triggerData),
            'notifiable_type' => 'user',
            'notifiable_id' => $config['user_id'] ?? 0,
            'data' => $triggerData,
        ]);

        return ['success' => true, 'action' => 'Notification sent'];
    }

    protected function actionUpdateLead(array $config, array $triggerData): array
    {
        $leadId = $triggerData['lead_id'] ?? $config['lead_id'] ?? null;
        if (! $leadId) return ['success' => false, 'error' => 'No lead ID'];

        $lead = Lead::find($leadId);
        if (! $lead) return ['success' => false, 'error' => 'Lead not found'];

        $newStage = $config['stage'] ?? null;
        if ($newStage) {
            $lead->update(['stage' => $newStage]);
        }

        return ['success' => true, 'action' => 'Lead updated'];
    }

    protected function actionAssignConversation(array $config, array $triggerData): array
    {
        $conversationId = $triggerData['conversation_id'] ?? null;
        if (! $conversationId) return ['success' => false, 'error' => 'No conversation ID'];

        $conversation = Conversation::find($conversationId);
        if (! $conversation) return ['success' => false, 'error' => 'Conversation not found'];

        $conversation->update([
            'assigned_user_id' => $config['user_id'] ?? null,
            'status' => 'assigned',
        ]);

        return ['success' => true, 'action' => 'Conversation assigned'];
    }

    protected function actionCreateWebhook(array $config, array $triggerData): array
    {
        // Fire a webhook call
        $url = $config['url'] ?? '';
        if (! $url) return ['success' => false, 'error' => 'No webhook URL'];

        try {
            \Illuminate\Support\Facades\Http::timeout(30)
                ->post($url, [
                    'event' => $config['event'] ?? 'automation.triggered',
                    'data' => $triggerData,
                ]);
            return ['success' => true, 'action' => 'Webhook called'];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    protected function actionSendEmail(array $config, array $triggerData): array
    {
        $to = $this->renderTemplate($config['to'] ?? '', $triggerData) ?: config('mail.from.address');
        $subject = $this->renderTemplate($config['subject'] ?? 'Automation Notification', $triggerData);
        $body = $this->renderTemplate($config['body'] ?? '', $triggerData);
        $fromAddress = $config['from_address'] ?? config('mail.from.address');
        $fromName = $config['from_name'] ?? config('mail.from.name', 'AI Automation');

        if (empty($to) || empty($body)) {
            return ['success' => false, 'error' => 'Missing email recipient or body.'];
        }

        try {
            Mail::raw($body, function ($message) use ($to, $subject, $fromAddress, $fromName, $config) {
                $message->from($fromAddress, $fromName)
                    ->to($to)
                    ->subject($subject);

                if (! empty($config['cc'])) {
                    $message->cc(explode(',', $config['cc']));
                }
                if (! empty($config['bcc'])) {
                    $message->bcc(explode(',', $config['bcc']));
                }
                if (! empty($config['reply_to'])) {
                    $message->replyTo($config['reply_to']);
                }
            });

            return ['success' => true, 'action' => "Email sent to {$to}"];

        } catch (\Exception $e) {
            Log::error('Automation email send failed', [
                'to' => $to,
                'subject' => $subject,
                'error' => $e->getMessage(),
            ]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Replace template variables like {{customer_name}} in message strings.
     */
    protected function renderTemplate(string $template, array $data): string
    {
        foreach ($data as $key => $value) {
            if (is_scalar($value)) {
                $template = str_replace('{{' . $key . '}}', (string) $value, $template);
            }
        }
        return $template;
    }

    /**
     * Check daily rate limits.
     */
    protected function checkRateLimit(Automation $automation): bool
    {
        $maxPerDay = $automation->max_executions_per_day;
        if (! $maxPerDay) return true;

        return ($automation->execution_count_today ?? 0) < $maxPerDay;
    }

    /**
     * Trigger automations on important business events.
     */
    public function triggerOnLeadCreated(Lead $lead): void
    {
        $this->process('new_lead', [
            'lead_id' => $lead->id,
            'customer_id' => $lead->customer_id,
            'customer_name' => $lead->customer?->first_name . ' ' . $lead->customer?->last_name,
            'lead_score' => $lead->score,
            'lead_stage' => $lead->stage,
            'product_interest' => $lead->product_interest,
        ], $lead->organization_id);
    }

    public function triggerOnMessageReceived(Message $message, Conversation $conversation, Customer $customer): void
    {
        $this->process('new_message', [
            'message_id' => $message->id,
            'conversation_id' => $conversation->id,
            'customer_id' => $customer->id,
            'customer_name' => $customer->first_name . ' ' . $customer->last_name,
            'message_content' => $message->content,
            'channel' => $conversation->channel,
        ], $message->organization_id);
    }

    public function triggerOnOrderCreated(Order $order): void
    {
        $this->process('order_created', [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'customer_id' => $order->customer_id,
            'total' => $order->total,
            'status' => $order->status,
        ], $order->organization_id);
    }

    public function triggerOnLeadStageChanged(Lead $lead, string $fromStage, string $toStage): void
    {
        $this->process('lead_stage_changed', [
            'lead_id' => $lead->id,
            'customer_id' => $lead->customer_id,
            'customer_name' => $lead->customer?->first_name . ' ' . $lead->customer?->last_name,
            'lead_score' => $lead->score,
            'lead_stage' => $toStage,
            'from_stage' => $fromStage,
            'to_stage' => $toStage,
            'product_interest' => $lead->product_interest,
            'estimated_value' => $lead->estimated_value,
        ], $lead->organization_id);
    }
}
