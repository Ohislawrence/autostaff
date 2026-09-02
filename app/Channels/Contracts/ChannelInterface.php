<?php

namespace App\Channels\Contracts;

interface ChannelInterface
{
    /**
     * Get the channel identifier (e.g., 'web_chat', 'whatsapp', 'email').
     */
    public function getIdentifier(): string;

    /**
     * Get the channel display name.
     */
    public function getName(): string;

    /**
     * Process an incoming message from this channel.
     * Returns normalized message data.
     */
    public function processIncoming(array $payload): array;

    /**
     * Send an outgoing message through this channel.
     */
    public function sendMessage(string $recipient, string $content, array $metadata = []): array;

    /**
     * Verify an incoming webhook request is legitimate.
     */
    public function verifyWebhook(array $payload, array $headers = []): bool;

    /**
     * Get channel configuration schema for setup UI.
     */
    public function getConfigSchema(): array;
}