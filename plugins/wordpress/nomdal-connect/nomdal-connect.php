<?php
/**
 * Plugin Name:       Nomdal Connect
 * Plugin URI:        https://nomdal.com/plugins/nomdal-connect
 * Description:       Connect your WordPress site to your Nomdal AI Employee. Sync leads, customers, and WooCommerce orders through the Nomdal API.
 * Version:           1.0.0
 * Author:            Nomdal
 * Author URI:        https://nomdal.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       nomdal-connect
 * Requires at least: 5.6
 * Requires PHP:      7.4
 */

if (! defined('ABSPATH')) {
    exit;
}

define('NOMDAL_CONNECT_VERSION', '1.0.0');
define('NOMDAL_CONNECT_FILE', __FILE__);
define('NOMDAL_CONNECT_PATH', plugin_dir_path(__FILE__));

require_once NOMDAL_CONNECT_PATH.'includes/ApiResponse.php';
require_once NOMDAL_CONNECT_PATH.'includes/ApiClient.php';
require_once NOMDAL_CONNECT_PATH.'includes/Admin.php';
require_once NOMDAL_CONNECT_PATH.'includes/Heartbeat.php';
require_once NOMDAL_CONNECT_PATH.'includes/Actions/Leads.php';
require_once NOMDAL_CONNECT_PATH.'includes/Actions/Woo.php';

// Keep the heartbeat cron in sync when the plugin is activated/deactivated.
register_activation_hook(__FILE__, [\Nomdal\Heartbeat::class, 'activate']);
register_deactivation_hook(__FILE__, [\Nomdal\Heartbeat::class, 'deactivate']);

\Nomdal\Admin::init();
\Nomdal\Actions\Leads::init();
\Nomdal\Actions\Woo::init();
\Nomdal\Heartbeat::init();
