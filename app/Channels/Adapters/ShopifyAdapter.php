<?php

namespace App\Channels\Adapters;

use App\Channels\Contracts\ChannelInterface;

class ShopifyAdapter implements ChannelInterface
{
    public function getIdentifier(): string
    {
        return 'shopify';
    }

    public function getName(): string
    {
        return 'Shopify';
    }

    public function processIncoming(array $payload): array
    {
        return [
            'message' => '',
            'channel' => 'shopify',
            'metadata' => [],
        ];
    }

    public function sendMessage(string $recipient, string $content, array $metadata = []): array
    {
        return ['success' => false, 'error' => 'Shopify is a store integration, not a messaging channel.'];
    }

    public function verifyWebhook(array $payload, array $headers = []): bool
    {
        return true;
    }

    public function getConfigSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'shop_domain' => [
                    'type' => 'string',
                    'description' => 'Your Shopify shop domain (e.g. your-store.myshopify.com)',
                ],
                'access_token' => [
                    'type' => 'string',
                    'description' => 'Shopify Admin API access token (shpat_...)',
                ],
                'webhook_secret' => [
                    'type' => 'string',
                    'description' => 'Shopify app client secret used to verify webhooks',
                ],
            ],
            'required' => ['shop_domain', 'access_token'],
        ];
    }
}
