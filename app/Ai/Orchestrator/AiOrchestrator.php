<?php

namespace App\Ai\Orchestrator;

use App\Ai\ContextBuilder\AiContextBuilder;
use App\Ai\Providers\AiProviderInterface;
use App\Ai\Providers\AiResponse;
use App\Ai\Understanding\IntentService;
use App\Models\AiEmployee;
use App\Models\AiRun;
use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Message;
use App\Ai\Tools\ToolExecutor;
use App\Services\Billing\AiCostEstimator;
use App\Services\ConversationService;
use App\Services\Guardrails\CostGuardService;
use App\Services\Guardrails\FallbackManager;
use App\Services\Knowledge\KnowledgeGapService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AiOrchestrator
{
    protected int $maxToolCalls = 5;
    protected int $maxDepth = 3;
    protected int $currentDepth = 0;

    public function __construct(
        protected AiProviderInterface $aiProvider,
        protected AiContextBuilder $contextBuilder,
        protected ConversationService $conversationService,
        protected ToolExecutor $toolExecutor,
        protected AiCostEstimator $costEstimator,
        protected ?IntentService $intentService = null,
        protected ?KnowledgeGapService $knowledgeGapService = null,
        protected ?CostGuardService $costGuard = null,
        protected ?FallbackManager $fallbackManager = null,
    ) {}

    /**
     * Process an incoming message and generate an AI response.
     * This is the main entry point for all AI interactions.
     *
     * @param Message|null $incomingMessage Pre-created incoming message (avoids duplicate when caller already created it)
     */
    public function processIncomingMessage(
        AiEmployee $employee,
        Conversation $conversation,
        Customer $customer,
        string $messageContent,
        array $channelMetadata = [],
        ?Message $incomingMessage = null,
    ): array {
        $startTime = microtime(true);
        $correlationId = (string) Str::uuid();

        try {
            // Set organization context for tenant scoping
            app()->instance('current_organization_id', $employee->organization_id);

            // 1. Check for escalation
            if ($this->conversationService->shouldEscalate($conversation, $employee, $messageContent)) {
                return $this->handleEscalation($employee, $conversation, $customer, $messageContent, $correlationId);
            }

            // 2. Create incoming message record (skip if already created by caller)
            if (! $incomingMessage) {
                $incomingMessage = $this->conversationService->createIncomingMessage($conversation, $customer, $messageContent, $channelMetadata);
            }

            // 2a. GUARD — block AI processing when the tenant has exceeded its monthly AI budget.
            if ($budgetBlocked = $this->budgetBlocked($employee, $conversation, $correlationId)) {
                return $budgetBlocked;
            }

            // 2b. UNDERSTAND — derive structured intent/entities/sentiment
            $understanding = $this->understand($messageContent);

            // Persist understanding onto the message metadata for observability
            if (! empty($understanding)) {
                $metadata = $incomingMessage->metadata ?? [];
                $metadata['understanding'] = $understanding;
                $incomingMessage->update(['metadata' => $metadata]);
            }

            // LEARN — record a knowledge gap when the AI has low confidence.
            $this->recordLowConfidenceGap($employee->organization_id, $messageContent, $understanding);

            // 3. Build AI context with structured prompt
            $messages = $this->contextBuilder->build($employee, $conversation, $customer, $understanding);

            // Add the current user message
            $messages[] = ['role' => 'user', 'content' => $messageContent];

            // 4. Get available tools for this employee
            $availableTools = $this->getAvailableTools($employee);

            // 5. Call AI provider
            $options = [
                'model' => $employee->ai_model ?? 'deepseek-chat',
                'temperature' => $employee->temperature ?? 0.7,
                'tools' => $availableTools,
            ];

            // Cost-saving degraded mode when the tenant is near its budget ceiling.
            if ($this->isDegradedMode($employee->organization_id)) {
                $options['temperature'] = 0.3;
                $options['max_tokens'] = 512;
                $options['degraded_mode'] = true;
            }

            $response = $this->aiProvider->chat($messages, $options);

            // 6. Handle tool calls if present
            $toolCallsMade = [];
            $toolResults = [];

            if (! empty($response->toolCalls)) {
                $toolCallsResult = $this->handleToolCalls(
                    $employee,
                    $conversation,
                    $response,
                    $messages,
                    $correlationId
                );
                $toolCallsMade = $toolCallsResult['calls'];
                $toolResults = $toolCallsResult['results'];
                $response = $toolCallsResult['final_response'];
            }

            // 7. Create AI response message
            $responseContent = $response->content ?: $this->fallbackResponseFor($response);

            $aiMessage = $this->conversationService->createAiResponse(
                $conversation,
                $employee,
                $responseContent,
                [
                    'ai_model' => $response->model,
                    'input_tokens' => $response->inputTokens,
                    'output_tokens' => $response->outputTokens,
                    'latency_ms' => $response->latencyMs,
                    'provider' => $response->metadata['provider'] ?? 'deepseek',
                    'tool_calls' => $toolCallsMade,
                    'correlation_id' => $correlationId,
                ]
            );

            // 8. Log AI run for observability
            $this->logAiRun(
                $employee,
                $conversation,
                $aiMessage,
                $response,
                $correlationId,
                $messages,
                $toolCallsMade,
                $toolResults,
                $this->contextBuilder->getRetrievedKnowledge()
            );

            return [
                'success' => true,
                'message' => $aiMessage,
                'response' => $responseContent,
                'tool_calls' => $toolCallsMade,
                'correlation_id' => $correlationId,
                'latency_ms' => (int) ((microtime(true) - $startTime) * 1000),
            ];

        } catch (\Exception $e) {
            // LEARN — record provider/processing failures as knowledge gaps.
            $this->recordGap($employee->organization_id, $messageContent, 'failed_tool', $e->getMessage());

            Log::error('AI Orchestrator error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'correlation_id' => $correlationId,
                'employee_id' => $employee->id,
                'conversation_id' => $conversation->id,
            ]);

            // GUARD — give the customer a graceful message instead of silence.
            $fallbackContext = $this->fallbackContextForMessage($e->getMessage());
            $this->conversationService->createAiResponse(
                $conversation,
                $employee,
                $this->fallbackManager?->getCannedResponse($fallbackContext)
                    ?? 'I apologize, but I was unable to process your request. Please try again or contact our support team.',
                [
                    'fallback' => true,
                    'context' => $fallbackContext,
                    'correlation_id' => $correlationId,
                ]
            );

            // Create error message in conversation
            $this->conversationService->createSystemEvent(
                $conversation,
                'AI processing error. Escalating to human team.',
                ['error' => $e->getMessage(), 'correlation_id' => $correlationId]
            );

            $this->conversationService->escalateToHuman(
                $conversation,
                'AI processing error',
                'The AI system encountered an error while processing the customer message.'
            );

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'escalated' => true,
                'correlation_id' => $correlationId,
            ];
        }
    }

    /**
     * UNDERSTAND — derive structured interpretation of the incoming message.
     */
    protected function understand(string $messageContent): array
    {
        if (! $this->intentService) {
            return [];
        }

        try {
            // Deterministic understanding only in the hot path: the response
            // "chat" call below is the single LLM round-trip. This keeps the
            // stage cheap/latency-free and avoids a double provider call.
            return $this->intentService->analyze($messageContent, [], false);
        } catch (\Throwable $e) {
            Log::warning('Intent service failed in orchestrator', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Handle tool calls from the AI response.
     */
    protected function handleToolCalls(
        AiEmployee $employee,
        Conversation $conversation,
        AiResponse $initialResponse,
        array $messages,
        string $correlationId
    ): array {
        $calls = [];
        $results = [];
        $currentMessages = $messages;

        // Add the assistant's tool call message
        $assistantMessage = ['role' => 'assistant', 'content' => $initialResponse->content];
        if (! empty($initialResponse->toolCalls)) {
            $assistantMessage['tool_calls'] = $initialResponse->toolCalls;
        }
        $currentMessages[] = $assistantMessage;

        // Execute each tool call
        foreach ($initialResponse->toolCalls as $toolCall) {
            $toolName = $toolCall['function']['name'] ?? 'unknown';
            $toolArgs = json_decode($toolCall['function']['arguments'] ?? '{}', true) ?: [];
            $toolCallId = $toolCall['id'] ?? (string) Str::uuid();

            $calls[] = ['name' => $toolName, 'arguments' => $toolArgs];

            // Execute the tool
            $result = $this->executeTool($employee, $conversation, $toolName, $toolArgs, $correlationId);
            $results[] = ['name' => $toolName, 'result' => $result];

            // Add tool result to conversation
            $currentMessages[] = [
                'role' => 'tool',
                'tool_call_id' => $toolCallId,
                'content' => json_encode($result),
            ];
        }

        // Get final response after tool execution
        $options = [
            'model' => $employee->ai_model ?? 'deepseek-chat',
            'temperature' => $employee->temperature ?? 0.7,
        ];

        // GUARD — re-check budget before the final LLM call in the tool loop.
        if ($this->costGuard && ! $this->costGuard->checkBudget($employee->organization_id)) {
            return [
                'calls' => $calls,
                'results' => $results,
                'final_response' => new AiResponse(
                    content: $this->budgetMessage(),
                    model: $employee->ai_model ?? 'deepseek-chat',
                    metadata: ['provider' => 'deepseek', 'budget_blocked' => true],
                ),
            ];
        }

        $finalResponse = $this->aiProvider->chat($currentMessages, $options);

        return [
            'calls' => $calls,
            'results' => $results,
            'final_response' => $finalResponse,
        ];
    }

    /**
     * Execute a specific tool for an employee.
     */
    protected function executeTool(
        AiEmployee $employee,
        Conversation $conversation,
        string $toolName,
        array $arguments,
        string $correlationId
    ): array {
        // Use the ToolExecutor for full permission checking, confirmation, and execution
        return $this->toolExecutor->execute(
            $employee,
            $conversation,
            $toolName,
            $arguments,
            $correlationId,
        );
    }

    /**
     * Get available tools formatted for the AI provider.
     */
    protected function getAvailableTools(AiEmployee $employee): array
    {
        $tools = $employee->tools()
            ->where('is_active', true)
            ->wherePivot('is_allowed', true)
            ->get();

        $formatted = [];
        foreach ($tools as $tool) {
            $formatted[] = [
                'type' => 'function',
                'function' => [
                    'name' => $tool->identifier,
                    'description' => $tool->description,
                    'parameters' => $tool->input_schema,
                ],
            ];
        }

        return $formatted;
    }

    /**
     * Handle conversation escalation.
     */
    protected function handleEscalation(
        AiEmployee $employee,
        Conversation $conversation,
        Customer $customer,
        string $messageContent,
        string $correlationId
    ): array {
        $this->conversationService->createIncomingMessage($conversation, $customer, $messageContent);

        // LEARN — record the escalation as a knowledge gap.
        $this->recordGap($employee->organization_id, $messageContent, 'escalation');

        // Send a response indicating handoff.
        $escalationMessage = "I'm going to transfer you to a member of our team who can better assist you. Please hold on while I connect you with a human agent.";

        $this->conversationService->createAiResponse($conversation, $employee, $escalationMessage, [
            'escalation' => true,
            'correlation_id' => $correlationId,
        ]);

        // Escalate LAST so the conversation ends in `human_required`
        // (createAiResponse would otherwise reset it back to `ai_handling`).
        $summary = "Customer message: {$messageContent}. Escalation triggered automatically based on escalation rules.";
        $this->conversationService->escalateToHuman($conversation, 'Automatic escalation trigger', $summary);

        return [
            'success' => true,
            'escalated' => true,
            'response' => $escalationMessage,
            'correlation_id' => $correlationId,
        ];
    }

    /**
     * Log an AI run for observability and cost tracking.
     */
    protected function logAiRun(
        AiEmployee $employee,
        Conversation $conversation,
        Message $message,
        AiResponse $response,
        string $correlationId,
        array $messages,
        array $toolCalls,
        array $toolResults,
        array $knowledgeRetrieved = []
    ): void {
        try {
            $systemPrompt = '';
            foreach ($messages as $msg) {
                if (($msg['role'] ?? '') === 'system') {
                    $systemPrompt = $msg['content'] ?? '';
                    break;
                }
            }

            // Get user prompt (last user message)
            $userPrompt = '';
            $lastUserIndex = count($messages) - 1;
            while ($lastUserIndex >= 0) {
                if (($messages[$lastUserIndex]['role'] ?? '') === 'user') {
                    $userPrompt = $messages[$lastUserIndex]['content'] ?? '';
                    break;
                }
                $lastUserIndex--;
            }

            AiRun::create([
                'organization_id' => $employee->organization_id,
                'ai_employee_id' => $employee->id,
                'conversation_id' => $conversation->id,
                'message_id' => $message->id,
                'provider' => $response->metadata['provider'] ?? 'deepseek',
                'model' => $response->model,
                'input_tokens' => $response->inputTokens,
                'output_tokens' => $response->outputTokens,
                'latency_ms' => $response->latencyMs,
                'tools_called' => $toolCalls,
                'knowledge_retrieved' => $knowledgeRetrieved,
                'system_prompt' => mb_substr($systemPrompt, 0, 5000),
                'user_prompt' => mb_substr($userPrompt, 0, 2000),
                'assistant_response' => mb_substr($response->content, 0, 5000),
                'estimated_cost' => $this->estimateCost($response),
                'status' => 'success',
                'correlation_id' => $correlationId,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to log AI run', ['error' => $e->getMessage()]);
        }
    }

    /**
     * LEARN — record a knowledge gap, silently ignoring failures.
     */
    protected function recordGap(int $organizationId, string $question, string $category, ?string $suggested = null): void
    {
        if (! $this->knowledgeGapService) {
            return;
        }

        try {
            $this->knowledgeGapService->record($organizationId, $question, $category, $suggested);
        } catch (\Throwable $e) {
            Log::warning('Failed to record knowledge gap', ['error' => $e->getMessage()]);
        }
    }

    /**
     * LEARN — record a gap when the UNDERSTAND stage produced low confidence.
     */
    protected function recordLowConfidenceGap(int $organizationId, string $question, array $understanding): void
    {
        $confidence = $understanding['confidence'] ?? null;
        if ($confidence !== null && (float) $confidence < 0.5) {
            $this->recordGap($organizationId, $question, 'low_confidence');
        }
    }

    /**
     * GUARD — return a budget-blocked result when the tenant has exceeded its
     * monthly AI budget. Returns null when processing may continue.
     */
    protected function budgetBlocked(AiEmployee $employee, Conversation $conversation, string $correlationId): ?array
    {
        if (! $this->costGuard || $this->costGuard->checkBudget($employee->organization_id)) {
            return null;
        }

        $message = $this->budgetMessage();

        $aiMessage = $this->conversationService->createAiResponse(
            $conversation,
            $employee,
            $message,
            [
                'budget_blocked' => true,
                'correlation_id' => $correlationId,
            ]
        );

        Log::warning('AI request blocked by budget guard', [
            'organization_id' => $employee->organization_id,
            'employee_id' => $employee->id,
            'conversation_id' => $conversation->id,
            'correlation_id' => $correlationId,
        ]);

        return [
            'success' => true,
            'budget_blocked' => true,
            'message' => $aiMessage,
            'response' => $message,
            'correlation_id' => $correlationId,
        ];
    }

    /**
     * GUARD — the canned message shown to customers when the budget is exhausted.
     */
    protected function budgetMessage(): string
    {
        return $this->fallbackManager?->getCannedResponse('budget')
            ?? 'I\'m currently operating in limited mode. Please contact our team directly for immediate assistance with this request.';
    }

    /**
     * GUARD — whether the tenant is near (but not yet over) its budget ceiling.
     */
    protected function isDegradedMode(int $organizationId): bool
    {
        return $this->costGuard?->isDegradedMode($organizationId) ?? false;
    }

    /**
     * GUARD — resolve a graceful message when the provider returns empty content.
     */
    protected function fallbackResponseFor(AiResponse $response): string
    {
        $error = $response->metadata['error'] ?? '';

        return $this->fallbackManager?->getCannedResponse($this->fallbackContextForMessage($error))
            ?? 'I apologize, but I was unable to process your request. Please try again or contact our support team.';
    }

    /**
     * Map an error message to a fallback context (timeout/offline/error).
     */
    protected function fallbackContextForMessage(string $message): string
    {
        $message = strtolower($message);

        if (str_contains($message, 'timeout') || str_contains($message, 'timed out')) {
            return 'timeout';
        }

        if (str_contains($message, 'offline') || str_contains($message, 'unavailable') || str_contains($message, 'connection')) {
            return 'offline';
        }

        return 'error';
    }

    /**
     * Estimate cost based on token usage (DeepSeek pricing).
     */
    protected function estimateCost(AiResponse $response): float
    {
        return $this->costEstimator->estimate($response);
    }
}