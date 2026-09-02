<?php

namespace App\Ai\Providers;

class AiResponse
{
    public function __construct(
        public readonly string $content,
        public readonly ?string $model = null,
        public readonly int $inputTokens = 0,
        public readonly int $outputTokens = 0,
        public readonly ?int $latencyMs = null,
        public readonly ?string $finishReason = null,
        public readonly array $toolCalls = [],
        public readonly array $metadata = [],
    ) {}

    public function toArray(): array
    {
        return [
            'content' => $this->content,
            'model' => $this->model,
            'input_tokens' => $this->inputTokens,
            'output_tokens' => $this->outputTokens,
            'latency_ms' => $this->latencyMs,
            'finish_reason' => $this->finishReason,
            'tool_calls' => $this->toolCalls,
            'metadata' => $this->metadata,
        ];
    }

    public static function empty(): self
    {
        return new self(content: '');
    }
}