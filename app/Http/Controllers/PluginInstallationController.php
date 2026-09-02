<?php

namespace App\Http\Controllers;

use App\Models\Plugin;
use App\Models\PluginInstallation;
use App\Services\Plugins\PluginInstallationService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PluginInstallationController extends Controller
{
    public function __construct(protected PluginInstallationService $installations) {}

    public function index()
    {
        $organization = current_org();

        $installations = $organization
            ? PluginInstallation::query()
                ->with(['plugin', 'pluginVersion', 'apiKey'])
                ->where('organization_id', $organization->id)
                ->latest()
                ->get()
                ->map(fn (PluginInstallation $i) => [
                    'id' => $i->id,
                    'plugin' => $i->plugin ? [
                        'name' => $i->plugin->name,
                        'slug' => $i->plugin->slug,
                        'icon' => $i->plugin->icon,
                    ] : null,
                    'version' => $i->pluginVersion?->version,
                    'site_url' => $i->site_url,
                    'site_label' => $i->site_label,
                    'status' => $i->status,
                    'api_key' => $i->apiKey ? [
                        'id' => $i->apiKey->id,
                        'key_prefix' => $i->apiKey->key,
                        'scopes' => $i->apiKey->scopes ?? [],
                    ] : null,
                    'last_seen_at' => $i->last_seen_at?->toISOString(),
                    'created_at' => $i->created_at->toISOString(),
                ])
            : collect();

        return Inertia::render('Plugins/Installations', ['installations' => $installations]);
    }

    public function store(Request $request, Plugin $plugin)
    {
        $organization = current_org();
        abort_if(! $organization, 403, 'No active organization.');
        abort_unless($plugin->is_published, 404, 'This plugin is not published.');

        $validated = $request->validate([
            'site_url' => ['required', 'url', 'max:255'],
            'site_label' => ['nullable', 'string', 'max:255'],
        ]);

        $result = $this->installations->install(
            $organization,
            $plugin,
            $validated['site_url'],
            $validated['site_label'] ?? null
        );

        return back()
            ->with('success', 'Plugin installed. Copy your API key and signing secret now — they will not be shown again.')
            ->with('api_key', $result['key'])
            ->with('signing_secret', $result['signing_secret']);
    }

    public function revoke(PluginInstallation $installation)
    {
        $this->authorizeOwnership($installation);
        $this->installations->revoke($installation);

        return back()->with('success', 'Installation revoked.');
    }

    public function regenerate(PluginInstallation $installation)
    {
        $this->authorizeOwnership($installation);
        $result = $this->installations->regenerate($installation);

        return back()
            ->with('success', 'API key and signing secret regenerated. Copy them now — they will not be shown again.')
            ->with('api_key', $result['key'])
            ->with('signing_secret', $result['signing_secret']);
    }

    protected function authorizeOwnership(PluginInstallation $installation): void
    {
        $organization = current_org();

        abort_if(
            ! $organization || $installation->organization_id !== $organization->id,
            403,
            'This installation does not belong to your organization.'
        );
    }
}
