<?php

namespace Tests\Feature;

use App\Http\Middleware\AuthenticateApiKey;
use App\Http\Middleware\CheckApiKeyScope;
use App\Models\Organization;
use App\Services\Api\ApiKeyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ApiKeyTest extends TestCase
{
    use RefreshDatabase;

    protected Organization $organization;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organization = Organization::create([
            'name' => 'API Key Org',
            'slug' => 'api-key-org',
            'onboarding_completed' => true,
        ]);
    }

    #[Test]
    public function it_issues_a_key_and_hashes_the_secret()
    {
        $service = app(ApiKeyService::class);
        $result = $service->issue($this->organization, 'WooCommerce Site', ['leads:create']);

        $this->assertArrayHasKey('key', $result);
        $this->assertStringContainsString('.', $result['key']);

        $apiKey = $result['apiKey'];
        $this->assertSame('WooCommerce Site', $apiKey->name);
        $this->assertSame(['leads:create'], $apiKey->scopes);
        $this->assertNotSame($result['key'], $apiKey->hashed_key);
        $this->assertTrue(Hash::check($result['key'], $apiKey->hashed_key));
        $this->assertNotNull($apiKey->uuid);
    }

    #[Test]
    public function it_regenerates_and_revokes_keys()
    {
        $service = app(ApiKeyService::class);
        $result = $service->issue($this->organization, 'Key', []);
        $apiKey = $result['apiKey'];

        $newKey = $service->regenerate($apiKey);
        $this->assertNotSame($result['key'], $newKey);
        $this->assertTrue(Hash::check($newKey, $apiKey->fresh()->hashed_key));
        $this->assertTrue($apiKey->fresh()->is_active);

        $service->revoke($apiKey->fresh());
        $this->assertFalse($apiKey->fresh()->is_active);
        $this->assertNotNull($apiKey->fresh()->revoked_at);
        $this->assertFalse($apiKey->fresh()->isUsable());
    }

    #[Test]
    public function it_authenticates_a_valid_bearer_key()
    {
        $service = app(ApiKeyService::class);
        $result = $service->issue($this->organization, 'Valid', []);
        $fullKey = $result['key'];

        $request = Request::create('/api/v1/test', 'GET');
        $request->headers->set('Authorization', 'Bearer '.$fullKey);

        $passed = false;
        app(AuthenticateApiKey::class)->handle($request, function () use (&$passed) {
            $passed = true;

            return response()->noContent();
        });

        $this->assertTrue($passed);
        $this->assertSame($this->organization->id, app('current_organization_id'));
        $this->assertNotNull(app('current_api_key'));
    }

    #[Test]
    public function it_rejects_missing_or_invalid_keys()
    {
        $middleware = app(AuthenticateApiKey::class);

        $missing = $middleware->handle(
            Request::create('/api/v1/test', 'GET'),
            fn () => response()->noContent()
        );
        $this->assertSame(401, $missing->getStatusCode());

        $invalid = Request::create('/api/v1/test', 'GET');
        $invalid->headers->set('Authorization', 'Bearer ak_nope.nope');
        $invalidResponse = $middleware->handle($invalid, fn () => response()->noContent());
        $this->assertSame(401, $invalidResponse->getStatusCode());
    }

    #[Test]
    public function it_rejects_revoked_keys()
    {
        $service = app(ApiKeyService::class);
        $result = $service->issue($this->organization, 'Revoked', []);
        $service->revoke($result['apiKey']);

        $request = Request::create('/api/v1/test', 'GET');
        $request->headers->set('Authorization', 'Bearer '.$result['key']);

        $response = app(AuthenticateApiKey::class)->handle($request, fn () => response()->noContent());

        $this->assertSame(401, $response->getStatusCode());
    }

    #[Test]
    public function it_enforces_scopes()
    {
        $service = app(ApiKeyService::class);
        $result = $service->issue($this->organization, 'Scoped', ['leads:create']);

        app()->instance('current_api_key', $result['apiKey']);

        $middleware = app(CheckApiKeyScope::class);

        $allowed = $middleware->handle(
            Request::create('/api/v1/leads', 'POST'),
            fn () => response()->noContent(),
            'leads:create'
        );
        $this->assertSame(204, $allowed->getStatusCode());

        $denied = $middleware->handle(
            Request::create('/api/v1/orders', 'POST'),
            fn () => response()->noContent(),
            'orders:create'
        );
        $this->assertSame(403, $denied->getStatusCode());
    }
}
