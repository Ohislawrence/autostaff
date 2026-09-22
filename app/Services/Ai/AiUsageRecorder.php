<?php

namespace App\Services\Ai;

use App\Ai\Providers\AiResponse;
use App\Models\AiRun;
use App\Services\Billing\AiCostEstimator;
use Illuminate\Support\Facades\Log;

/**
 * Records non-conversational AI calls (e.g. the Prospecting pipeline) as
 * AiRun rows so they are included in cost tracking and budget enforcement.
 */
class AiUsageRecorder
{
    public function __construct(protected AiCostEstimator $costEstimator) {}

    public function record(int $organizationId, AiResponse $response, array $context = []): ?AiRun
    {
        try {
            return AiRun::create([
                'organization_id' => $organizationId,
                'provider' => $response->metadata['provider'] ?? 'deepseek',
                'model' => $response->model,
                'input_tokens' => $response->inputTokens,
                'output_tokens' => $response->outputTokens,
                'latency_ms' => $response->latencyMs,
                'system_prompt' => isset($context['system_prompt']) ? mb_substr((string) $context['system_prompt'], 0, 5000) : null,
                'user_prompt' => isset($context['user_prompt']) ? mb_substr((string) $context['user_prompt'], 0, 2000) : null,
                'assistant_response' => mb_substr((string) $response->content, 0, 5000),
                'estimated_cost' => $this->costEstimator->estimate($response),
                'status' => 'success',
                'correlation_id' => $context['correlation_id'] ?? null,
                'tools_called' => $context['tools_called'] ?? [],
                'knowledge_retrieved' => $context['knowledge_retrieved'] ?? [],
            ]);
        } catch (\Throwable $e) {
            Log::warning('Failed to record AI usage', [
                'organization_id' => $organizationId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
