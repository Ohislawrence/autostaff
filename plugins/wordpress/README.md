# Nomdal Plugins

This directory holds the distributable plugin packages that tenants can install
on their own apps (WordPress first). Each plugin is a self-contained package.

## WordPress — Nomdal Connect

Location: `wordpress/nomdal-connect/`

A reference WordPress plugin that connects a tenant's WordPress site to their
Nomdal platform through a scoped API key:

- **Settings page** (Settings → Nomdal) for Base URL + API key + "Test Connection".
- **Contact Form 7** submissions are sent to Nomdal as leads.
- **WooCommerce** orders are synced (customer find-or-create + order with line items).
- **Heartbeat** support so the platform can show when the site last connected.

## Packaging & uploading

A ready-to-install package is produced as `wordpress/nomdal-connect-1.0.0.zip`
(the `nomdal-connect/` folder is the top-level directory, per WordPress convention).

To rebuild it:

```
cd wordpress
zip -r nomdal-connect-1.0.0.zip nomdal-connect
```

To publish in the platform catalog (admin → `/platform/plugins`):

1. Create the plugin and upload `nomdal-connect-1.0.0.zip` as a version.
2. Publish the version, then publish the plugin.
3. Tenants download it from `/plugins`, install it on their WordPress site,
   and paste their API key + signing secret into Settings → Nomdal.

## Adding a new platform

Create a sibling folder under `plugins/` (e.g. `shopify/`), build the package,
and register it in the platform with the matching `target_platform`.
