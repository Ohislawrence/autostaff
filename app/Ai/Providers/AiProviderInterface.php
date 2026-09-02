<?php

namespace App\Ai\Providers;

interface AiProviderInterface
{
    /**
     * Send a chat completion request to the AI provider.
     *
     * @param array<int, array<string, string>> $messages
     * @param array<string, mixed> $options
     * @return AiResponse
     */
    public function chat(array $messages, array $options = []): AiResponse;

    /**
     * Generate embeddings for given texts.
     *
     * @param string|array<int, string> $texts
     * @return array<int, array<int, float>>
     */
    public function embed(string|array $texts): array;

    /**
     * Get the provider name.
     */
    public function getName(): string;

    /**
     * Get available models.
     *
     * @return array<int, string>
     */
    public function getModels(): array;

    /**
     * Check if the provider is available (health check).
     */
    public function isAvailable(): bool;
}