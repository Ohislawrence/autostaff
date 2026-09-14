<?php

/**
 * Nomdal Connect for Shopify
 *
 * Connects a Shopify store to the Nomdal platform: receives Shopify webhooks
 * and syncs orders + customers into Nomdal, plus an optional heartbeat.
 *
 * This is a standalone PHP app (not a WordPress plugin). Deploy it anywhere
 * reachable by Shopify and point your webhooks at `webhook.php`.
 */

define('NOMDAL_CONNECT_VERSION', '1.0.0');
define('NOMDAL_CONNECT_PATH', __DIR__);

require_once NOMDAL_CONNECT_PATH.'/includes/ApiResponse.php';
require_once NOMDAL_CONNECT_PATH.'/includes/Config.php';
require_once NOMDAL_CONNECT_PATH.'/includes/NomdalClient.php';
require_once NOMDAL_CONNECT_PATH.'/includes/Shopify/ShopifyClient.php';
require_once NOMDAL_CONNECT_PATH.'/includes/Shopify/Sync.php';
require_once NOMDAL_CONNECT_PATH.'/includes/Shopify/Heartbeat.php';
