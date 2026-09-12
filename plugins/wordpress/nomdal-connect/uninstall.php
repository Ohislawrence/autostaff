<?php

// Exit if uninstall is not called from WordPress.
if (! defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

wp_clear_scheduled_hook('nomdal_connect_heartbeat');

delete_option('nomdal_base_url');
delete_option('nomdal_api_key');
delete_option('nomdal_signing_secret');
