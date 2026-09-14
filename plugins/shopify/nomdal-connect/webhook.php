<?php

/**
 * Webhook receiver. Point Shopify webhooks here:
 *   - orders/create   -> https://your-host/nomdal-connect/webhook.php
 *   - customers/create -> https://your-host/nomdal-connect/webhook.php
 */

require __DIR__.'/nomdal-connect.php';

use Nomdal\Config;
use Nomdal\NomdalClient;
use Nomdal\Shopify\ShopifyClient;
use Nomdal\Shopify\Sync;

$config = Config::load();

$rawBody = file_get_contents('php://input');
$topic = $_SERVER['HTTP_X_SHOPIFY_TOPIC'] ?? '';
$hmac = $_SERVER['HTTP_X_SHOPIFY_HMAC_SHA256'] ?? '';

$shopify = new ShopifyClient(
    $config['shopify_domain'],
    $config['shopify_access_token'],
    $config['shopify_api_version'],
    $config['shopify_webhook_secret']
);

if (! $shopify->verifyWebhook($rawBody, $hmac)) {
    http_response_code(401);
    exit('Invalid signature');
}

$payload = json_decode($rawBody, true);
if (! is_array($payload)) {
    http_response_code(400);
    exit('Invalid payload');
}

$nomdal = new NomdalClient(
    $config['nomdal_base_url'],
    $config['nomdal_api_key'],
    30,
    $config['nomdal_signing_secret']
);

$sync = new Sync($nomdal);

if ($topic === 'orders/create') {
    $sync->syncOrder($payload);
} elseif ($topic === 'customers/create') {
    $sync->syncCustomer(['email' => $payload['email'] ?? '', 'customer' => $payload]);
}

http_response_code(200);
exit('OK');
