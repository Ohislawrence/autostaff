<?php

namespace Tests\Feature;

use App\Http\Controllers\PluginDirectoryController;
use App\Models\Plugin;
use App\Models\PluginDownload;
use App\Models\PluginVersion;
use App\Services\Plugins\PluginPackageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

class PluginDirectoryTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_lists_only_published_plugins()
    {
        $published = Plugin::create(['name' => 'Published', 'slug' => 'published', 'is_published' => true]);
        Plugin::create(['name' => 'Draft', 'slug' => 'draft', 'is_published' => false]);

        PluginVersion::create([
            'plugin_id' => $published->id,
            'version' => '1.0.0',
            'file_path' => 'published/1.0.0/plugin.zip',
            'status' => 'published',
            'is_current' => true,
            'published_at' => now(),
        ]);

        $response = app(PluginDirectoryController::class)->index();
        $component = (fn () => $this->component)->call($response);
        $props = (fn () => $this->props)->call($response);

        $this->assertSame('Plugins/Directory', $component);
        $this->assertCount(1, $props['plugins']);
        $this->assertSame('published', $props['plugins'][0]['slug']);
        $this->assertSame('1.0.0', $props['plugins'][0]['current_version']['version']);
    }

    #[Test]
    public function it_streams_and_records_a_download()
    {
        Storage::fake('plugins');

        $plugin = Plugin::create(['name' => 'WP Connect', 'slug' => 'wp-connect', 'is_published' => true]);
        $file = UploadedFile::fake()->create('plugin.zip', 512, 'application/zip');

        $service = app(PluginPackageService::class);
        $version = $service->uploadVersion($plugin, '1.0.0', $file);
        $service->publish($version);

        $response = app(PluginDirectoryController::class)->download($plugin->fresh(), $version->fresh());

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(1, PluginDownload::count());
        $this->assertSame(1, $plugin->fresh()->downloads_count);
        $this->assertStringContainsString('attachment', $response->headers->get('Content-Disposition'));
    }

    #[Test]
    public function it_blocks_downloads_for_unpublished_versions()
    {
        $plugin = Plugin::create(['name' => 'WP Connect', 'slug' => 'wp-connect', 'is_published' => true]);
        $version = PluginVersion::create([
            'plugin_id' => $plugin->id,
            'version' => '1.0.0',
            'file_path' => 'wp-connect/1.0.0/plugin.zip',
            'status' => 'draft',
            'is_current' => false,
        ]);

        $this->expectException(NotFoundHttpException::class);
        app(PluginDirectoryController::class)->download($plugin, $version);
    }
}
