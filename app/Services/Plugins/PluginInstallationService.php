<?php

namespace App\Services\Plugins;

use App\Models\Organization;
use App\Models\Plugin;
use App\Models\PluginInstallation;
use App\Services\Api\ApiKeyService;
use Illuminate\Support\Str;

class PluginInstallationService
{
    public const DEFAULT_SCOPES = [
        'leads:read',
        'leads:create',
        'customers:read',
        'customers:create',
        'orders:read',
        'orders:create',
        'messages:send',
        'conversations:read',
        'ai-employees:read',
    ];

    public function __construct(protected ApiKeyService $keys) {}

    /**
     * Create an installation and issue a scoped API key (returned once).
     *
     * @return array{key: string, installation: PluginInstallation}
     */
    public function install(Organization $organization, Plugin $plugin, string $siteUrl, ?string $siteLabel = null): array
    {
        $currentVersion = $plugin->versions()
            ->where('status', 'published')
            ->where('is_current', true)
            ->first();

        $scopes = $plugin->scopes && count($plugin->scopes) > 0
            ? $plugin->scopes
            : self::DEFAULT_SCOPES;

        $installation = PluginInstallation::create([
            'organization_id' => $organization->id,
            'plugin_id' => $plugin->id,
            'plugin_version_id' => $currentVersion?->id,
            'site_url' => $siteUrl,
            'site_label' => $siteLabel,
            'status' => 'active',
            'signing_secret' => Str::random(48),
        ]);

        $result = $this->keys->issue(
            $organization,
            $this->nameFor($plugin, $siteUrl, $siteLabel),
            $scopes,
            null,
            $installation->id
        );

        $installation->update(['api_key_id' => $result['apiKey']->id]);

        audit_log('plugin_installed', "Installed \"{$plugin->name}\" on {$siteUrl}", [
            'plugin' => $plugin->slug,
            'site_url' => $siteUrl,
        ]);

        return [
            'key' => $result['key'],
            'signing_secret' => $installation->signing_secret,
            'installation' => $installation->fresh(),
        ];
    }

    public function revoke(PluginInstallation $installation): void
    {
        if ($installation->api_key_id) {
            $this->keys->revoke($installation->apiKey);
        }

        $installation->update(['status' => 'revoked']);

        audit_log('plugin_installation_revoked', "Revoked installation on {$installation->site_url}", [
            'plugin' => $installation->plugin?->slug,
            'site_url' => $installation->site_url,
        ]);
    }

    public function regenerate(PluginInstallation $installation): array
    {
        $signingSecret = Str::random(48);

        if (! $installation->api_key_id) {
            $organization = $installation->organization;
            $plugin = $installation->plugin;
            $scopes = ($plugin->scopes && count($plugin->scopes) > 0) ? $plugin->scopes : self::DEFAULT_SCOPES;

            $result = $this->keys->issue(
                $organization,
                $this->nameFor($plugin, $installation->site_url, $installation->site_label),
                $scopes,
                null,
                $installation->id
            );

            $installation->update([
                'api_key_id' => $result['apiKey']->id,
                'status' => 'active',
                'signing_secret' => $signingSecret,
            ]);

            audit_log('plugin_installation_regenerated', "Regenerated key and signing secret for {$installation->site_url}");

            return ['key' => $result['key'], 'signing_secret' => $signingSecret];
        }

        $newKey = $this->keys->regenerate($installation->apiKey);
        $installation->update(['status' => 'active', 'signing_secret' => $signingSecret]);

        audit_log('plugin_installation_regenerated', "Regenerated key and signing secret for {$installation->site_url}");

        return ['key' => $newKey, 'signing_secret' => $signingSecret];
    }

    protected function nameFor(Plugin $plugin, string $siteUrl, ?string $siteLabel = null): string
    {
        return trim(($siteLabel ? $siteLabel.' — ' : '').$plugin->name.' ('.$siteUrl.')');
    }
}
