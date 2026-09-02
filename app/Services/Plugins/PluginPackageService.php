<?php

namespace App\Services\Plugins;

use App\Models\Plugin;
use App\Models\PluginVersion;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class PluginPackageService
{
    protected string $disk = 'plugins';

    /**
     * Store an uploaded package, compute its SHA-256 checksum, and create
     * the corresponding version record (in "draft" status).
     */
    public function uploadVersion(Plugin $plugin, string $version, UploadedFile $package, ?string $changelog = null): PluginVersion
    {
        $relativeDir = $plugin->slug.'/'.$version;
        $path = $package->storeAs($relativeDir, 'plugin.zip', $this->disk);

        $absolutePath = Storage::disk($this->disk)->path($path);

        return PluginVersion::create([
            'plugin_id' => $plugin->id,
            'version' => $version,
            'changelog' => $changelog,
            'file_path' => $path,
            'file_name' => $package->getClientOriginalName(),
            'file_size' => $package->getSize(),
            'sha256_checksum' => hash_file('sha256', $absolutePath),
            'status' => 'draft',
            'is_current' => false,
        ]);
    }

    /**
     * Publish a version and make it the current version for its plugin.
     */
    public function publish(PluginVersion $version): void
    {
        PluginVersion::query()
            ->where('plugin_id', $version->plugin_id)
            ->where('id', '!=', $version->id)
            ->update(['is_current' => false]);

        $version->update([
            'status' => 'published',
            'is_current' => true,
            'published_at' => now(),
        ]);

        // A plugin with a published version is automatically visible to tenants.
        $version->plugin()->update(['is_published' => true]);
    }

    /**
     * Deprecate a version, promoting the latest published version if needed.
     */
    public function deprecate(PluginVersion $version): void
    {
        $wasCurrent = $version->is_current;

        $version->update([
            'status' => 'deprecated',
            'is_current' => false,
        ]);

        if ($wasCurrent) {
            $next = PluginVersion::query()
                ->where('plugin_id', $version->plugin_id)
                ->where('status', 'published')
                ->where('id', '!=', $version->id)
                ->latest('published_at')
                ->first();

            if ($next) {
                $next->update(['is_current' => true]);
            }
        }
    }

    /**
     * Delete a version and its stored package file.
     */
    public function delete(PluginVersion $version): void
    {
        if ($version->file_path) {
            Storage::disk($this->disk)->delete($version->file_path);
        }

        $version->delete();
    }
}
