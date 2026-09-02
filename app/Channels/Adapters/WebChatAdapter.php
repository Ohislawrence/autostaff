<?php

namespace App\Channels\Adapters;

use App\Channels\Contracts\ChannelInterface;

class WebChatAdapter implements ChannelInterface
{
    public function getIdentifier(): string
    {
        return 'web_chat';
    }

    public function getName(): string
    {
        return 'Website Chat Widget';
    }

    public function processIncoming(array $payload): array
    {
        return [
            'customer_name' => $payload['customer_name'] ?? 'Website Visitor',
            'customer_email' => $payload['customer_email'] ?? null,
            'customer_phone' => $payload['customer_phone'] ?? null,
            'message' => $payload['message'] ?? '',
            'channel' => 'web_chat',
            'channel_conversation_id' => $payload['session_id'] ?? null,
            'metadata' => [
                'page_url' => $payload['page_url'] ?? null,
                'user_agent' => $payload['user_agent'] ?? null,
            ],
        ];
    }

    public function sendMessage(string $recipient, string $content, array $metadata = []): array
    {
        // Web chat responses are delivered via the chat API response
        return [
            'success' => true,
            'channel' => 'web_chat',
            'content' => $content,
        ];
    }

    public function verifyWebhook(array $payload, array $headers = []): bool
    {
        // Web chat uses API authentication (Sanctum token or public UUID)
        // Verification is done at the middleware/controller level
        return true;
    }

    public function getConfigSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'greeting' => ['type' => 'string', 'description' => 'Welcome greeting message'],
                'primary_color' => ['type' => 'string', 'description' => 'Widget primary color (hex)'],
                'position' => ['type' => 'string', 'description' => 'Widget position: bottom-right or bottom-left'],
                'logo_url' => ['type' => 'string', 'description' => 'URL for the widget logo'],
            ],
        ];
    }
}