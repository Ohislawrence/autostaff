<?php

namespace Nomdal;

class Heartbeat
{
    public const HOOK = 'nomdal_connect_heartbeat';

    public static function init(): void
    {
        add_action(self::HOOK, [self::class, 'run']);

        // Re-schedule if the event is missing (e.g. after a plugin update).
        if (! wp_next_scheduled(self::HOOK)) {
            self::schedule();
        }
    }

    public static function activate(): void
    {
        self::schedule();
    }

    public static function deactivate(): void
    {
        wp_clear_scheduled_hook(self::HOOK);
    }

    public static function schedule(): void
    {
        if (! wp_next_scheduled(self::HOOK)) {
            wp_schedule_event(time() + HOUR_IN_SECONDS, 'hourly', self::HOOK);
        }
    }

    public static function run(): void
    {
        $client = ApiClient::fromSettings();
        if (! $client) {
            return;
        }

        $response = $client->heartbeat();

        if (! $response->success) {
            error_log('[Nomdal Connect] Heartbeat failed: '.$response->message);
        }
    }
}
