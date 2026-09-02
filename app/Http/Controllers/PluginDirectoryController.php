<?php

namespace App\Http\Controllers;

use App\Models\Plugin;
use App\Models\PluginDownload;
use App\Models\PluginVersion;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class PluginDirectoryController extends Controller
{
    public function index()
    {
        $plugins = Plugin::with('versions')
            ->where('is_published', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(function (Plugin $plugin) {
                $current = $plugin->versions->first(
                    fn ($v) => $v->is_current && $v->status === 'published'
                );

                return [
                    'id' => $plugin->id,
                    'slug' => $plugin->slug,
                    'name' => $plugin->name,
                    'icon' => $plugin->icon,
                    'short_description' => $plugin->short_description,
                    'description' => $plugin->description,
                    'author' => $plugin->author,
                    'homepage_url' => $plugin->homepage_url,
                    'category' => $plugin->category,
                    'target_platform' => $plugin->target_platform,
                    'downloads_count' => $plugin->downloads_count,
                    'current_version' => $current ? [
                        'id' => $current->id,
                        'version' => $current->version,
                        'changelog' => $current->changelog,
                        'file_size' => $current->file_size,
                        'sha256_checksum' => $current->sha256_checksum,
                        'published_at' => $current->published_at?->toISOString(),
                    ] : null,
                ];
            });

        return Inertia::render('Plugins/Directory', ['plugins' => $plugins]);
    }

    public function download(Plugin $plugin, PluginVersion $version)
    {
        abort_unless(
            $plugin->is_published
                && $version->plugin_id === $plugin->id
                && $version->status === 'published',
            404
        );

        PluginDownload::create([
            'organization_id' => current_org_id(),
            'plugin_version_id' => $version->id,
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        $plugin->increment('downloads_count');

        return Storage::disk('plugins')->download(
            $version->file_path,
            sprintf('%s-%s.zip', $plugin->slug, $version->version)
        );
    }
}
