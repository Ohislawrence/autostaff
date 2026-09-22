Done. Here are the **short** and **full** descriptions for the WordPress plugin **Nomdal Connect**, which I also wrote into the plugin's `manifest.json` and `readme.txt`.

## Short description

> Connect your WordPress site to your Nomdal AI Employee — sync leads, customers, and WooCommerce orders securely.

## Full description

> Nomdal Connect links your WordPress site to your Nomdal platform through a secure, scoped API key, so your AI Employee always has your leads and orders in view.
>
> - Send Contact Form 7 submissions to Nomdal as leads.
> - Sync WooCommerce orders and customers to Nomdal (find-or-create plus order line items, de-duplicated).
> - Report in with an hourly heartbeat so you can see when the site last connected.
> - Secure by default: scoped Bearer API key plus optional HMAC-SHA256 request signing.
> - Settings page with a one-click Test Connection.
>
> Requirements: WordPress 5.6+ and PHP 7.4+. Contact Form 7 and WooCommerce integrations activate automatically when those plugins are installed.

## Where I saved it
- `plugins/wordpress/nomdal-connect/manifest.json` — added `short_description` and `description` (validated as valid JSON).
- `plugins/wordpress/nomdal-connect/readme.txt` — expanded the `== Description ==` section.

Note: the `Plugin` model already exposes `short_description` and `description` (entered in the platform admin at **Platform → Plugins**), so this copy is ready to paste there or reuse from the manifest.