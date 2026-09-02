<?php

namespace Tests\Feature;

use App\Http\Controllers\PluginInstallationController;
use App\Models\Organization;
use App\Models\Plugin;
use App\Models\PluginVersion;
use App\Services\Plugins\PluginInstallationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PluginInstallationTest extends TestCase
{
    use RefreshDatabase;

    protected Organization $organization;
    protected Plugin $plugin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organization = Organization::create([
            'name' => 'Install Org',
            'slug' => 'install-org',
            'onboarding_completed' => true,
        ]);

        $this->plugin = Plugin::create([
            'name' => 'WordPress Connect',
            'slug' => 'wordpress-connect',
            'is_published' => true,
        ]);

        PluginVersion::create([
            'plugin_id' => $this->plugin->id,
            'version' => '1.0.0',
            'file_path' => 'wordpress-connect/1.0.0/plugin.zip',
            'status' => 'published',
            'is_current' => true,
            'published_at' => now(),
        ]);
    }

    #[Test]
    public function it_installs_a_plugin_and_issues_a_scoped_key()
    {
        $service = app(PluginInstallationService::class);
        $result = $service->install($this->organization, $this->plugin, 'https://example.com', 'Main store');

        $this->assertArrayHasKey('key', $result);
        $this->assertStringContainsString('.', $result['key']);
        $this->assertArrayHasKey('signing_secret', $result);
        $this->assertNotEmpty($result['signing_secret']);

        $installation = $result['installation'];
        $this->assertSame('active', $installation->status);
        $this->assertSame('https://example.com', $installation->site_url);
        $this->assertNotNull($installation->plugin_version_id);
        $this->assertNotNull($installation->api_key_id);
        $this->assertNotNull($installation->uuid);
        $this->assertSame($result['signing_secret'], $installation->signing_secret);

        $apiKey = $installation->apiKey;
        $this->assertSame($installation->id, $apiKey->plugin_installation_id);
        $this->assertTrue(Hash::check($result['key'], $apiKey->hashed_key));
        $this->assertNotEmpty($apiKey->scopes);
    }

    #[Test]
    public function it_regenerates_and_revokes_an_installation()
    {
        $service = app(PluginInstallationService::class);
        $result = $service->install($this->organization, $this->plugin, 'https://example.com');

        $regenerated = $service->regenerate($result['installation']);
        $this->assertNotSame($result['key'], $regenerated['key']);
        $this->assertNotSame($result['signing_secret'], $regenerated['signing_secret']);
        $this->assertTrue(Hash::check($regenerated['key'], $result['installation']->fresh()->apiKey->hashed_key));
        $this->assertSame($regenerated['signing_secret'], $result['installation']->fresh()->signing_secret);

        $service->revoke($result['installation']->fresh());

        $installation = $result['installation']->fresh();
        $this->assertSame('revoked', $installation->status);
        $this->assertFalse($installation->apiKey->isUsable());
    }

    #[Test]
    public function it_lists_installations()
    {
        $service = app(PluginInstallationService::class);
        $service->install($this->organization, $this->plugin, 'https://example.com', 'Main store');

        app()->instance('current_organization', $this->organization);
        app()->instance('current_organization_id', $this->organization->id);

        $response = app(PluginInstallationController::class)->index();
        $component = (fn () => $this->component)->call($response);
        $props = (fn () => $this->props)->call($response);

        $this->assertSame('Plugins/Installations', $component);
        $this->assertCount(1, $props['installations']);
        $this->assertSame('wordpress-connect', $props['installations'][0]['plugin']['slug']);
    }
}
