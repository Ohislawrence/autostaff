=== Nomdal Connect ===
Contributors: nomdal
Tags: nomdal, ai, crm, woocommerce, leads, customers
Requires at least: 5.6
Tested up to: 6.5
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Connect your WordPress site to your Nomdal AI Employee.

== Description ==

Nomdal Connect links your WordPress site to your Nomdal platform through a secure, scoped API key, so your AI Employee always has your leads and orders in view.

* Send Contact Form 7 submissions to Nomdal as leads.
* Sync WooCommerce orders and customers to Nomdal (find-or-create plus order line items, de-duplicated).
* Report in with an hourly heartbeat so you can see when the site last connected.
* Secure by default: scoped Bearer API key plus optional HMAC-SHA256 request signing.
* Settings page with a one-click Test Connection.

Requirements: WordPress 5.6+ and PHP 7.4+. Contact Form 7 and WooCommerce integrations activate automatically when those plugins are installed.

== Installation ==

1. Upload the `nomdal-connect` folder to `/wp-content/plugins/`, or install the zip via Plugins → Add New → Upload Plugin.
2. Activate the plugin through the "Plugins" screen.
3. Go to Settings → Nomdal.
4. Paste your Nomdal Base URL, API key, and Signing Secret (generated when you install the plugin in your Nomdal dashboard).
5. Click "Test Connection" to verify.

== Changelog ==

= 1.0.0 =
* Initial release.
