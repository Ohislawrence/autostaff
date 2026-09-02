<?php

namespace Tests\Feature;

use App\Http\Controllers\McpConnectionController;
use App\Models\McpConnection;
use App\Models\Organization;
use App\Models\Tool;
use App\Services\Mcp\McpConnectionManager;
use App\Services\Mcp\McpToolRegistrar;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class McpHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected Organization $organization;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organization = Organization::create([
            'name' => 'Hardening Org',
            'slug' => 'hardening-org',
            'onboarding_completed' => true,
        ]);
        app()->instance('current_organization', $this->organization);
        app()->instance('current_organization_id', $this->organization->id);
    }

    protected function fakeServer(array $tools): void
    {
        Http::fake(function ($request) use ($tools) {
            $method = $request->data()['method'] ?? '';
            $id = $request->data()['id'] ?? 0;

            $result = match ($method) {
                'initialize' => ['serverInfo' => ['name' => 'demo']],
                'tools/list' => ['tools' => $tools],
                'tools/call' => ['content' => [['type' => 'text', 'text' => 'ok']], 'isError' => false],
                default => [],
            };

            return Http::response(['jsonrpc' => '2.0', 'id' => $id, 'result' => $result]);
        });
    }

    protected function makeConnection(): McpConnection
    {
        return app(McpConnectionManager::class)->store($this->organization->id, [
            'provider' => 'google_calendar',
            'endpoint' => 'https://mcp.invalid',
            'credentials' => ['access_token' => 'secret'],
        ]);
    }

    #[Test] public function it_does_not_resolve_tools_when_feature_disabled()
    {
        $connection = $this->makeConnection();
        $this->fakeServer([['name' => 'create_event', 'description' => 'Create event']]);
        app(McpToolRegistrar::class)->sync($connection);

        $tool = Tool::where('organization_id', $this->organization->id)->where('is_custom', true)->first();
        $this->assertNotNull($tool);

        config(['mcp.enabled' => false]);
        try {
            $this->assertNull(app(McpToolRegistrar::class)->resolve($tool->identifier, $this->organization->id));
        } finally {
            config(['mcp.enabled' => true]);
        }
    }

    #[Test] public function it_sanitizes_tool_name_and_description_on_sync()
    {
        $connection = $this->makeConnection();
        $this->fakeServer([
            ['name' => "bad\x00name", 'description' => str_repeat('A', 2500) . "\x00evil"],
        ]);
        app(McpToolRegistrar::class)->sync($connection);

        $tool = Tool::where('organization_id', $this->organization->id)->where('is_custom', true)->first();

        $this->assertSame('badname', $tool->name);
        $this->assertSame(2000, mb_strlen($tool->description));
        $this->assertStringNotContainsString("\x00", $tool->description);
    }

    #[Test] public function it_rejects_private_endpoints_in_store()
    {
        $response = app(McpConnectionController::class)->store(new Request([
            'provider' => 'google_calendar',
            'endpoint' => 'http://127.0.0.1',
        ]));

        $this->assertStringContainsString('/integrations/mcp', $response->getTargetUrl());
        $this->assertSame(0, McpConnection::count());
    }

    #[Test] public function it_rejects_disallowed_providers_in_store()
    {
        config(['mcp.allowed_providers' => ['google_calendar']]);
        try {
            app(McpConnectionController::class)->store(new Request([
                'provider' => 'gmail',
                'endpoint' => 'https://mcp.invalid',
            ]));

            $this->assertSame(0, McpConnection::count());
        } finally {
            config(['mcp.allowed_providers' => ['google_calendar', 'gmail', 'crm', 'microsoft_365', 'custom']]);
        }
    }
}
