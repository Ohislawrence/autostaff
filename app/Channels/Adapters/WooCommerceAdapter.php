<?php

namespace App\Channels\Adapters;

use App\Channels\Contracts\ChannelInterface;

class WooCommerceAdapter implements ChannelInterface
{
    public function getIdentifier(): string
    {
        return 'woocommerce';
    }

    public function getName(): string
    {
        return 'WooCommerce';
    }

    public function processIncoming(array $payload): array
    {
        // WooCommerce is a store integration, not a messaging channel.
        return [
            'message' => '',
            'channel' => 'woocommerce',
            'metadata' => [],
        ];
    }

    public function sendMessage(string $recipient, string $content, array $metadata = []): array
    {
        return ['success' => false, 'error' => 'WooCommerce is a store integration, not a messaging channel.'];
    }

    public function verifyWebhook(array $payload, array $headers = []): bool
    {
        // WooCommerce webhook signature verification is handled in the webhook controller.
        return true;
    }

    public function getConfigSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'store_url' => [
                    'type' => 'string',
                    'description' => 'Your WooCommerce store URL (e.g. https://yourstore.com)',
                ],
                'consumer_key' => [
                    'type' => 'string',
                    'description' => 'WooCommerce REST API Consumer Key',
                ],
                'consumer_secret' => [
                    'type' => 'string',
                    'description' => 'WooCommerce REST API Consumer Secret',
                ],
                'webhook_secret' => [
                    'type' => 'string',
                    'description' => 'Secret used to verify incoming WooCommerce webhooks',
                ],
            ],
            'required' => ['store_url', 'consumer_key', 'consumer_secret'],
        ];
    }
}
