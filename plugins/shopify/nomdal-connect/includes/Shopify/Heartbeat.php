<?php

namespace Nomdal\Shopify;

use Nomdal\NomdalClient;

class Heartbeat
{
    /**
     * Report in to Nomdal so the platform can show when this store last connected.
     */
    public static function run(NomdalClient $client): bool
    {
        $response = $client->heartbeat();

        if (! $response->success) {
            error_log('[Nomdal Connect] Heartbeat failed: '.$response->message);
        }

        return $response->success;
    }
}
