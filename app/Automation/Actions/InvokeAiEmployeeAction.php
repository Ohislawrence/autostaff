<?php

namespace App\Automation\Actions;

use App\Ai\Orchestrator\AiOrchestrator;
use App\Automation\Contracts\ActionInterface;
use App\Models\AiEmployee;
use App\Models\Conversation;
use App\Models\Customer;
use App\Services\ConversationService;
use Illuminate\Support\Facades\Log;

class InvokeAiEmployeeAction implements ActionInterface
{
    public function __construct(
        protected ConversationService $conversationService,
    ) {}

    public function identifier(): string
    {
        return 'invoke_ai_employee';
    }

    public function label(): string
    {
        return 'Invoke AI Employee';
    }

    public function description(): string
    {
        return 'Have an AI Employee process a message or perform a task. This connects automations directly to your AI workforce.';
    }

    public function configSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'ai_employee_id' => [
                    'type' => 'integer',
                    'description' => 'The ID of the AI Employee to invoke.',
                ],
                'prompt' => [
                    'type' => 'string',
                    'description' => 'The prompt/message to send to the AI Employee. Use {{variable}} for template variables.',
                ],
                'create_conversation' => [
                    'type' => 'boolean',
                    'description' => 'Create a new conversation for the AI interaction. If false, uses the trigger conversation.',
                    'default' => false,
                ],
                'system_hint' => [
                    'type' => 'string',
                    'description' => 'Additional system-level instruction for this specific invocation.',
                ],
            ],
            'required' => ['ai_employee_id', 'prompt'],
        ];
    }

    public function execute(array $config, array $triggerData): array
    {
        $aiEmployeeId = $config['ai_employee_id'] ?? null;
        $prompt = $this->renderTemplate($config['prompt'] ?? '', $triggerData);
        $createConversation = $config['create_conversation'] ?? false;

        if (! $aiEmployeeId) {
            return ['success' => false, 'error' => 'No AI Employee ID specified'];
        }

        $aiEmployee = AiEmployee::find($aiEmployeeId);
        if (! $aiEmployee || ! $aiEmployee->is_active) {
            return ['success' => false, 'error' => 'AI Employee not found or is inactive'];
        }

        $organizationId = $aiEmployee->organization_id;
        app()->instance('current_organization_id', $organizationId);

        // Determine the conversation to use
        if ($createConversation || empty($triggerData['conversation_id'])) {
            // Get or create a customer
            $customerId = $triggerData['customer_id'] ?? null;
            $customer = null;

            if ($customerId) {
                $customer = Customer::find($customerId);
            }

            if (! $customer) {
                // Create a system customer for automation-driven conversations
                $customer = Customer::firstOrCreate(
                    [
                        'organization_id' => $organizationId,
                        'email' => 'automation@system.nomdal',
                    ],
                    [
                        'organization_id' => $organizationId,
                        'first_name' => 'Automation',
                        'last_name' => 'System',
                        'email' => 'automation@system.nomdal',
                        'source' => 'automation',
                        'channel' => 'automation',
                    ]
                );
            }

            $conversation = $this->conversationService->findOrCreateConversation(
                $aiEmployee->organization,
                $aiEmployee,
                $customer,
                'automation',
                'auto_' . ($triggerData['conversation_id'] ?? uniqid())
            );
        } else {
            $conversation = Conversation::find($triggerData['conversation_id']);
            if (! $conversation) {
                return ['success' => false, 'error' => 'Trigger conversation not found'];
            }
        }

        try {
            // Resolve the orchestrator from the container
            $orchestrator = app(AiOrchestrator::class);

            // Process the prompt through the AI Employee
            $result = $orchestrator->processIncomingMessage(
                $aiEmployee,
                $conversation,
                $conversation->customer,
                $prompt,
                [
                    'source' => 'automation',
                    'automation_action' => 'invoke_ai_employee',
                    'trigger_data' => array_keys($triggerData),
                ]
            );

            Log::info('Automation: AI Employee invoked', [
                'ai_employee_id' => $aiEmployeeId,
                'conversation_id' => $conversation->id,
                'success' => $result['success'] ?? false,
                'has_tool_calls' => ! empty($result['tool_calls']),
            ]);

            return [
                'success' => $result['success'] ?? false,
                'action' => 'AI Employee invoked',
                'ai_response' => $result['response'] ?? null,
                'tool_calls' => $result['tool_calls'] ?? [],
                'escalated' => $result['escalated'] ?? false,
                'conversation_id' => $conversation->uuid,
            ];
        } catch (\Exception $e) {
            Log::error('Automation: AI Employee invocation failed', [
                'ai_employee_id' => $aiEmployeeId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => 'AI invocation failed: ' . $e->getMessage(),
            ];
        }
    }

    protected function renderTemplate(string $template, array $data): string
    {
        foreach ($data as $key => $value) {
            if (is_scalar($value)) {
                $template = str_replace('{{' . $key . '}}', (string) $value, $template);
            }
        }
        return $template;
    }
}