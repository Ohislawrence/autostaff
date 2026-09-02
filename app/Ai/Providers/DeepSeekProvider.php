<?php

namespace App\Ai\Providers;

use Illuminate\Support\Facades\Log;
use OpenAI;
use OpenAI\Client;

class DeepSeekProvider implements AiProviderInterface
{
    protected Client $client;
    protected string $model;
    protected string $embeddingModel;

    public function __construct()
    {
        $apiKey = config('services.deepseek.api_key', env('DEEPSEEK_API_KEY', ''));
        $baseUrl = config('services.deepseek.base_url', env('DEEPSEEK_BASE_URL', 'https://api.deepseek.com'));
        $this->model = config('services.deepseek.model', env('DEEPSEEK_MODEL', 'deepseek-chat'));
        $this->embeddingModel = config('services.deepseek.embedding_model', env('DEEPSEEK_EMBEDDING_MODEL', 'deepseek-embedding'));

        $this->client = OpenAI::factory()
            ->withApiKey($apiKey)
            ->withBaseUri($baseUrl . '/v1')
            ->withHttpHeader('Accept', 'application/json')
            ->make();
    }

    public function chat(array $messages, array $options = []): AiResponse
    {
        $startTime = microtime(true);

        try {
            $model = $options['model'] ?? $this->model;
            $temperature = $options['temperature'] ?? 0.7;
            $maxTokens = $options['max_tokens'] ?? 2048;
            $tools = $options['tools'] ?? [];
            $toolChoice = $options['tool_choice'] ?? null;

            $params = [
                'model' => $model,
                'messages' => $messages,
                'temperature' => $temperature,
            ];

            // Only add max_tokens for non-reasoning models
            if (! str_contains($model, 'reasoner')) {
                $params['max_tokens'] = $maxTokens;
            }

            if (! empty($tools)) {
                $params['tools'] = $tools;
                if ($toolChoice) {
                    $params['tool_choice'] = $toolChoice;
                }
            }

            $response = $this->client->chat()->create($params);

            $latencyMs = (int) ((microtime(true) - $startTime) * 1000);

            $choice = $response->choices[0];
            $message = $choice->message;

            $toolCalls = [];
            if (! empty($message->toolCalls)) {
                foreach ($message->toolCalls as $toolCall) {
                    $toolCalls[] = [
                        'id' => $toolCall->id,
                        'type' => 'function',
                        'function' => [
                            'name' => $toolCall->function->name,
                            'arguments' => $toolCall->function->arguments,
                        ],
                    ];
                }
            }

            return new AiResponse(
                content: $message->content ?? '',
                model: $response->model,
                inputTokens: $response->usage->promptTokens ?? 0,
                outputTokens: $response->usage->completionTokens ?? 0,
                latencyMs: $latencyMs,
                finishReason: $choice->finishReason,
                toolCalls: $toolCalls,
                metadata: [
                    'provider' => 'deepseek',
                    'id' => $response->id,
                ],
            );

        } catch (\Exception $e) {
            Log::error('DeepSeek API error', [
                'error' => $e->getMessage(),
                'model' => $this->model,
            ]);

            // Return empty response on error
            $latencyMs = (int) ((microtime(true) - $startTime) * 1000);

            return new AiResponse(
                content: '',
                model: $this->model,
                latencyMs: $latencyMs,
                metadata: [
                    'provider' => 'deepseek',
                    'error' => $e->getMessage(),
                ],
            );
        }
    }

    public function embed(string|array $texts): array
    {
        $input = is_array($texts) ? $texts : [$texts];

        try {
            // Try with the configured embedding model
            $response = $this->client->embeddings()->create([
                'model' => $this->embeddingModel,
                'input' => $input,
            ]);

            $embeddings = [];
            foreach ($response->embeddings as $embedding) {
                $embeddings[] = $embedding->embedding;
            }

            if (! empty($embeddings)) {
                Log::info('DeepSeek embedding generated', [
                    'model' => $this->embeddingModel,
                    'inputs' => count($input),
                    'dimensions' => count($embeddings[0]),
                ]);
            }

            return $embeddings;
        } catch (\Exception $e) {
            Log::warning('DeepSeek embedding unavailable — falling back to keyword search', [
                'model' => $this->embeddingModel,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    public function getName(): string
    {
        return 'deepseek';
    }

    public function getModels(): array
    {
        return [
            'deepseek-chat',
            'deepseek-reasoner',
        ];
    }

    public function isAvailable(): bool
    {
        try {
            $this->chat([['role' => 'user', 'content' => 'ping']], ['max_tokens' => 1]);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get the underlying client for direct use.
     */
    public function getClient(): Client
    {
        return $this->client;
    }
}