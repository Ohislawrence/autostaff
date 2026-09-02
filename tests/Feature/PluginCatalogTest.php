<?php

namespace Tests\Feature;

use App\Http\Controllers\Platform\PluginController;
use App\Models\Plugin;
use App\Models\PluginVersion;
use App\Services\Plugins\PluginPackageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PluginCatalogTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_creates_a_plugin()
    {
        app(PluginController::class)->store(new Request([
            'name' => 'WordPress Connect',
            'slug' => 'wordpress-connect',
            'target_platform' => 'wordpress',
        ]));

        $plugin = Plugin::where('slug', 'wordpress-connect')->first();

        $this->assertNotNull($plugin);
        $this->assertNotNull($plugin->uuid);
        $this->assertFalse($plugin->is_published);
    }

    #[Test]
    public function it_creates_a_plugin_with_an_initial_version()
    {
        Storage::fake('plugins');

        $file = UploadedFile::fake()->create('plugin.zip', 512, 'application/zip');

        $request = Request::create('/platform/plugins', 'POST', [
            'name' => 'WP Connect',
            'slug' => 'wp-connect',
            'target_platform' => 'wordpress',
            'version' => '1.0.0',
        ], [], ['package' => $file]);

        app(PluginController::class)->store($request);

        $plugin = Plugin::where('slug', 'wp-connect')->first();
        $this->assertNotNull($plugin);
        $this->assertSame(1, $plugin->versions()->count());
        $this->assertSame('1.0.0', $plugin->versions()->first()->version);
    }

    #[Test]
    public function it_uploads_and_hashes_a_package()
    {
        Storage::fake('plugins');

        $plugin = Plugin::create(['name' => 'WP Connect', 'slug' => 'wp-connect']);
        $file = UploadedFile::fake()->create('plugin.zip', 2048, 'application/zip');

        $version = app(PluginPackageService::class)->uploadVersion($plugin, '1.0.0', $file, 'Initial release');

        $this->assertSame('1.0.0', $version->version);
        $this->assertSame('draft', $version->status);
        $this->assertGreaterThan(0, $version->file_size);
        $this->assertSame(64, strlen($version->sha256_checksum));
        Storage::disk('plugins')->assertExists($version->file_path);
    }

    #[Test]
    public function it_publishes_and_deprecates_versions()
    {
        $plugin = Plugin::create(['name' => 'WP Connect', 'slug' => 'wp-connect']);

        $v1 = PluginVersion::create([
            'plugin_id' => $plugin->id,
            'version' => '1.0.0',
            'file_path' => 'wp-connect/1.0.0/plugin.zip',
            'status' => 'published',
            'is_current' => true,
            'published_at' => now(),
        ]);

        $v2 = PluginVersion::create([
            'plugin_id' => $plugin->id,
            'version' => '1.1.0',
            'file_path' => 'wp-connect/1.1.0/plugin.zip',
            'status' => 'draft',
            'is_current' => false,
        ]);

        $service = app(PluginPackageService::class);
        $service->publish($v2);

        $this->assertTrue($v2->fresh()->is_current);
        $this->assertFalse($v1->fresh()->is_current);
        $this->assertSame('published', $v2->fresh()->status);
        $this->assertTrue($plugin->fresh()->is_published);

        $service->deprecate($v2->fresh());

        $this->assertFalse($v2->fresh()->is_current);
        $this->assertSame('deprecated', $v2->fresh()->status);
        $this->assertTrue($v1->fresh()->is_current); // promoted back
    }

    #[Test]
    public function it_uploads_a_version_via_controller()
    {
        Storage::fake('plugins');

        $plugin = Plugin::create(['name' => 'WP Connect', 'slug' => 'wp-connect']);
        $file = UploadedFile::fake()->create('plugin.zip', 512, 'application/zip');

        $request = Request::create('/platform/plugins/'.$plugin->id.'/versions', 'POST', [
            'version' => '1.0.0',
            'changelog' => 'Initial release',
        ], [], ['package' => $file]);

        app(PluginController::class)->storeVersion($request, $plugin);

        $this->assertSame(1, PluginVersion::where('plugin_id', $plugin->id)->count());
    }
}
