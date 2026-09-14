<?php

// Copy this file to `config.php` (same directory) and fill in your values.
// `config.php` is ignored by version control so secrets never leak.

return [
    // Shopify custom app (https://admin.shopify.com/store/<store>/settings/apps/development)
    'shopify_domain' => 'your-store.myshopify.com',
    'shopify_access_token' => '',          // Admin API access token (shpat_...)
    'shopify_api_version' => '2024-10',    // Admin API version
    'shopify_webhook_secret' => '',        // Shared secret used to verify webhooks (HMAC-SHA256)

    // Nomdal plugin installation (Platform → Plugins → Installations)
    'nomdal_base_url' => 'https://app.nomdal.com',
    'nomdal_api_key' => '',
    'nomdal_signing_secret' => '',         // Optional HMAC-SHA256 request signing
];
