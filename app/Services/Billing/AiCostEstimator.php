<?php

namespace App\Services\Billing;

use App\Ai\Providers\AiResponse;

/**
 * Converts an AI response's token usage into an estimated USD cost.
 *
 * Rates are USD per 1M tokens and reflect DeepSeek's public pricing.
 */
class AiCostEstimator
{
    protected const DEFAULT_MODEL = 'deepseek-chat';

    /** @var array<string, array{input: float, output: float}> */
    protected const PRICING = [
        'deepseek-chat' => ['input' => 0.14, 'output' => 0.28],
        'deepseek-reasoner' => ['input' => 0.55, 'output' => 2.19],
    ];

    public function estimate(AiResponse $response): float
    {
        $rates = $this->ratesFor($response->model ?: self::DEFAULT_MODEL);

        $inputCost = ($response->inputTokens / 1000000) * $rates['input'];
        $outputCost = ($response->outputTokens / 1000000) * $rates['output'];

        return round($inputCost + $outputCost, 6);
    }

    /**
     * @return array{input: float, output: float}
     */
    protected function ratesFor(string $model): array
    {
        foreach (self::PRICING as $needle => $rates) {
            if (str_contains(strtolower($model), $needle)) {
                return $rates;
            }
        }

        return self::PRICING[self::DEFAULT_MODEL];
    }
}
