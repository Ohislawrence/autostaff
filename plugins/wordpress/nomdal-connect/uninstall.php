<?php

// Exit if uninstall is not called from WordPress.
if (! defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

delete_option('nomdal_base_url');
delete_option('nomdal_api_key');
delete_option('nomdal_signing_secret');
