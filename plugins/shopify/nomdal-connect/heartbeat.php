<?php

/**
 * CLI / cron heartbeat entry point.
 *
 * Schedule with cron (e.g. hourly):
 *   0 * * * * php /path/to/nomdal-connect/heartbeat.php >> /dev/null 2>&1
 */

require __DIR__.'/nomdal-connect.php';

use Nomdal\Config;
use Nomdal\NomdalClient;
use Nomdal\Shopify\Heartbeat;

$config = Config::load();

$client = new NomdalClient(
    $config['nomdal_base_url'],
    $config['nomdal_api_key'],
    30,
    $config['nomdal_signing_secret']
);

exit(Heartbeat::run($client) ? 0 : 1);
