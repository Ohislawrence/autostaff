<?php

namespace Nomdal;

class Admin
{
    public static function init(): void
    {
        add_action('admin_menu', [self::class, 'registerMenu']);
        add_action('admin_init', [self::class, 'registerSettings']);
    }

    public static function registerMenu(): void
    {
        add_options_page(
            __('Nomdal Connect', 'nomdal-connect'),
            __('Nomdal', 'nomdal-connect'),
            'manage_options',
            'nomdal-connect',
            [self::class, 'renderPage']
        );
    }

    public static function registerSettings(): void
    {
        register_setting('nomdal_connect', 'nomdal_base_url', ['sanitize_callback' => 'esc_url_raw']);
        register_setting('nomdal_connect', 'nomdal_api_key', ['sanitize_callback' => 'sanitize_text_field']);
        register_setting('nomdal_connect', 'nomdal_signing_secret', ['sanitize_callback' => 'sanitize_text_field']);
    }

    public static function renderPage(): void
    {
        if (! current_user_can('manage_options')) {
            return;
        }

        $notice = null;

        if (isset($_POST['nomdal_test_connection']) && check_admin_referer('nomdal_test_connection')) {
            $client = ApiClient::fromSettings();
            $response = $client ? $client->heartbeat() : null;

            if ($response && $response->success) {
                $notice = ['type' => 'success', 'text' => __('Connection successful.', 'nomdal-connect')];
            } else {
                $text = $response
                    ? $response->message
                    : __('Please configure your Base URL and API key first.', 'nomdal-connect');
                $notice = ['type' => 'error', 'text' => $text];
            }
        }

        $baseUrl = get_option('nomdal_base_url', '');
        $apiKey = get_option('nomdal_api_key', '');
        $signingSecret = get_option('nomdal_signing_secret', '');
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Nomdal Connect', 'nomdal-connect'); ?></h1>
            <p><?php esc_html_e('Connect this WordPress site to your Nomdal AI Employee.', 'nomdal-connect'); ?></p>

            <?php if ($notice): ?>
                <div class="notice notice-<?php echo esc_attr($notice['type']); ?> is-dismissible">
                    <p><?php echo esc_html($notice['text']); ?></p>
                </div>
            <?php endif; ?>

            <form method="post" action="options.php">
                <?php settings_fields('nomdal_connect'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="nomdal_base_url"><?php esc_html_e('Base URL', 'nomdal-connect'); ?></label></th>
                        <td>
                            <input name="nomdal_base_url" id="nomdal_base_url" type="url" value="<?php echo esc_attr($baseUrl); ?>" class="regular-text" placeholder="https://nomdal.com" />
                            <p class="description"><?php esc_html_e('Your Nomdal platform URL (e.g. https://app.nomdal.com).', 'nomdal-connect'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="nomdal_api_key"><?php esc_html_e('API Key', 'nomdal-connect'); ?></label></th>
                        <td>
                            <input name="nomdal_api_key" id="nomdal_api_key" type="password" value="<?php echo esc_attr($apiKey); ?>" class="regular-text" autocomplete="off" />
                            <p class="description"><?php esc_html_e('Copy the API key from your Nomdal plugin installation.', 'nomdal-connect'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="nomdal_signing_secret"><?php esc_html_e('Signing Secret', 'nomdal-connect'); ?></label></th>
                        <td>
                            <input name="nomdal_signing_secret" id="nomdal_signing_secret" type="password" value="<?php echo esc_attr($signingSecret); ?>" class="regular-text" autocomplete="off" />
                            <p class="description"><?php esc_html_e('Optional. If provided, requests are signed with HMAC-SHA256.', 'nomdal-connect'); ?></p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(__('Save Settings', 'nomdal-connect')); ?>
            </form>

            <form method="post">
                <?php wp_nonce_field('nomdal_test_connection'); ?>
                <p>
                    <?php submit_button(__('Test Connection', 'nomdal-connect'), 'secondary', 'nomdal_test_connection'); ?>
                </p>
            </form>
        </div>
        <?php
    }
}
