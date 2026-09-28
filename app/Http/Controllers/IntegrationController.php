<?php

namespace App\Http\Controllers;

use App\Channels\ChannelManager;
use App\Models\Integration;
use App\Services\Commerce\ProductSyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;

class IntegrationController extends Controller
{
    public function __construct(protected ChannelManager $channels) {}

    /**
     * Show setup page for a specific integration channel.
     */
    public function setup(string $channel)
    {
        $organization = current_org();
        $adapter = $this->channels->get($channel);

        // Load existing integration for this org + provider
        $integration = $organization->integrations()
            ->where('provider', $channel)
            ->first();

        $config = $integration?->config ?? [];
        $savedKeys = [];
        foreach (Integration::SENSITIVE_CONFIG_KEYS as $key) {
            if (! empty($config[$key] ?? null)) {
                $savedKeys[] = $key;
                $config[$key] = '';
            }
        }
        $isConnected = $integration?->is_connected ?? false;

        return Inertia::render('Integrations/Setup', [
            'channel' => [
                'key' => $adapter->getIdentifier(),
                'name' => $adapter->getName(),
                'schema' => $adapter->getConfigSchema(),
            ],
            'integration' => $integration ? [
                'id' => $integration->id,
                'is_connected' => $isConnected,
                'config' => $config,
                'saved_keys' => $savedKeys,
                'status' => $integration->status,
                'last_synced_at' => $integration->last_synced_at?->toIso8601String(),
            ] : null,
        ]);
    }

    /**
     * Save or update integration config.
     */
    public function saveConfig(Request $request, string $channel)
    {
        $organization = current_org();
        $adapter = $this->channels->get($channel);
        $schema = $adapter->getConfigSchema();

        // Build validation rules from schema
        $rules = [];
        foreach ($schema['properties'] as $key => $prop) {
            $rule = [];
            if (($prop['type'] ?? 'string') === 'boolean') {
                $rule[] = 'boolean';
            } else {
                $rule[] = $schema['properties'][$key]['type'] === 'integer' ? 'integer' : 'string';
            }
            if (! empty($prop['nullable'] ?? false)) {
                $rule[] = 'nullable';
            }
            $rules[$key] = $rule;
        }
        if (! empty($schema['required'])) {
            foreach ($schema['required'] as $reqKey) {
                if (isset($rules[$reqKey])) {
                    $rules[$reqKey][] = 'required';
                } else {
                    $rules[$reqKey] = ['required', 'string'];
                }
            }
        }

        $validated = $request->validate($rules);

        $existing = $organization->integrations()->where('provider', $channel)->first();
        $existingConfig = $existing?->config ?? [];

        // Encrypt sensitive values; a blank sensitive field keeps the stored value.
        foreach (Integration::SENSITIVE_CONFIG_KEYS as $key) {
            if (! array_key_exists($key, $validated)) {
                continue;
            }

            if ($validated[$key] === '' || $validated[$key] === null) {
                $validated[$key] = $existingConfig[$key] ?? null;
            } else {
                $validated[$key] = Integration::encryptConfigValue((string) $validated[$key]);
            }
        }

        // Upsert integration record (UUID is auto-generated on create).
        $integration = $organization->integrations()->updateOrCreate(
            ['provider' => $channel],
            [
                'name' => $adapter->getName(),
                'config' => $validated,
                'is_connected' => true,
                'status' => 'active',
            ]
        );

        // Also store any credentials in integration_credentials
        $this->storeCredentials($integration, $validated, $adapter);

        return back()->with('success', ucfirst($channel) . ' configuration saved successfully.');
    }

    /**
     * Disconnect an integration.
     */
    public function disconnect(string $channel)
    {
        $organization = current_org();
        $integration = $organization->integrations()
            ->where('provider', $channel)
            ->first();

        if ($integration) {
            $integration->update([
                'is_connected' => false,
                'status' => 'inactive',
                'config' => null,
            ]);

            // Delete associated credentials
            $organization->integrationCredentials()
                ->where('integration_id', $integration->id)
                ->delete();
        }

        return back()->with('success', ucfirst($channel) . ' integration disconnected.');
    }

    /**
     * Trigger a store product sync (WooCommerce/Shopify).
     */
    public function sync(string $channel)
    {
        $organization = current_org();

        try {
            $result = app(ProductSyncService::class)->sync($organization);
        } catch (\Throwable $e) {
            return back()->with('error', 'Store sync failed: '.$e->getMessage());
        }

        if (empty($result['success'])) {
            return back()->with('error', 'Store sync failed: '.($result['error'] ?? 'Unknown error'));
        }

        return back()->with('success', "Products synced: {$result['imported']} imported, {$result['updated']} updated.");
    }

    /**
     * Test connectivity/credentials for an integration without saving first.
     */
    public function test(Request $request, string $channel)
    {
        if ($channel === 'woocommerce') {
            $storeUrl = rtrim((string) $request->input('store_url', ''), '/');

            if ($storeUrl === '') {
                return back()->with('error', 'Enter your store URL first.');
            }

            try {
                $response = Http::withBasicAuth(
                    (string) $request->input('consumer_key', ''),
                    (string) $request->input('consumer_secret', ''),
                )
                    ->timeout(30)
                    ->get($storeUrl.'/wp-json/wc/v3/products', ['per_page' => 1]);
            } catch (\Throwable $e) {
                return back()->with('error', 'Could not reach your store. Check the store URL is correct and reachable.');
            }

            if ($response->status() === 401 || $response->status() === 403) {
                return back()->with('error', 'Authentication failed — your Consumer Key or Secret is incorrect. Recreate the WooCommerce REST API key with Read access and re-enter it.');
            }

            if ($response->ok()) {
                return back()->with('success', 'Connection successful — your WooCommerce credentials work.');
            }

            return back()->with('error', 'Unexpected response from your store (HTTP '.$response->status().').');
        }

        return back()->with('error', 'Connection testing is not available for this integration.');
    }

    /**
     * Store encrypted credentials.
     */
    protected function storeCredentials(Integration $integration, array $config, $adapter): void
    {
        $organization = current_org();
        $credentialKeys = ['api_key', 'access_token', 'webhook_secret', 'password', 'secret'];

        foreach ($credentialKeys as $key) {
            if (! empty($config[$key])) {
                $organization->integrationCredentials()->updateOrCreate(
                    ['integration_id' => $integration->id, 'key' => $key],
                    [
                        'value' => encrypt($config[$key]),
                        'type' => 'encrypted',
                    ]
                );
            }
        }
    }
}