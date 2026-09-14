# Nomdal Connect for Shopify

A reference Shopify integration (custom app) that connects a tenant's Shopify
store to their Nomdal platform — the Shopify equivalent of the WordPress
"Nomdal Connect" plugin.

Unlike WordPress, Shopify has no in-store "plugins"; it uses **apps** that
receive **webhooks**. This package is a small, self-contained PHP webhook
receiver + heartbeat script that you deploy anywhere reachable by Shopify.

## What it does

- **Orders** — on `orders/create` webhooks, syncs the order into Nomdal
  (find-or-create customer + order with line items).
- **Customers** — on `customers/create` webhooks, syncs the customer into Nomdal.
- **Heartbeat** — posts to Nomdal so the platform can show when the store last
  connected.
- **Secure** — verifies Shopify webhooks with HMAC-SHA256, and signs Nomdal
  requests with a Bearer API key (+ optional HMAC-SHA256 request signing).

## Setup

### 1. Create a Shopify custom app

1. In Shopify Admin, go to **Settings → Apps and sales channels → Develop apps**.
2. Create an app and grant it read access to **Orders** and **Customers**
   (Admin API access scopes).
3. Install the app and copy the **Admin API access token** (`shpat_...`).
4. Note your **API secret key** (used as the webhook secret) and your store's
   **myshopify.com** domain.

### 2. Configure

Copy `config.example.php` to `config.php` (same folder) and fill in:

```php
return [
    'shopify_domain' => 'your-store.myshopify.com',
    'shopify_access_token' => 'shpat_...',
    'shopify_api_version' => '2024-10',
    'shopify_webhook_secret' => 'your-app-api-secret',
    'nomdal_base_url' => 'https://app.nomdal.com',
    'nomdal_api_key' => '...',
    'nomdal_signing_secret' => '...', // optional
];
```

### 3. Deploy

Upload this folder to any PHP 7.4+ host and make `webhook.php` publicly
reachable (e.g. `https://your-host/nomdal-connect/webhook.php`).

### 4. Register webhooks

In the Shopify custom app, add webhook subscriptions pointing at `webhook.php`:

- `orders/create`
- `customers/create`

### 5. Heartbeat

Schedule `heartbeat.php` with cron (hourly is fine):

```
0 * * * * php /path/to/nomdal-connect/heartbeat.php >> /dev/null 2>&1
```

## Publishing in the Nomdal catalog

From the platform admin (**Platform → Plugins**):

1. Create the plugin with `target_platform` = `shopify` and upload a zip of
   this folder (`nomdal-connect/` as the top-level directory).
2. Publish the version, then publish the plugin.
3. Tenants download it from `/plugins`, deploy it, and paste their Nomdal API
   key + signing secret into `config.php`.
