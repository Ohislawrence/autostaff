<?php

namespace Tests\Feature;

use App\Http\Controllers\AiEmployeeController;
use App\Models\AiEmployee;
use App\Models\Organization;
use App\Models\User;
use App\Services\Billing\UsageTracker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AiEmployeeControllerTest extends TestCase
{
    use RefreshDatabase;

    protected Organization $organization;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organization = Organization::create([
            'name' => 'AI Employee Org',
            'slug' => 'ai-employee-org',
            'onboarding_completed' => true,
        ]);

        app()->instance('current_organization', $this->organization);
        app()->instance('current_organization_id', $this->organization->id);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    protected function makeUser(string $role): User
    {
        Role::findOrCreate($role, 'web');

        $user = User::create([
            'name' => $role,
            'email' => strtolower(str_replace(' ', '.', $role)).'@example.com',
            'password' => bcrypt('password'),
        ]);
        $user->assignRole($role);

        return $user;
    }

    protected function requestAs(User $user, array $payload): Request
    {
        $request = new Request($payload);
        $request->setUserResolver(fn () => $user);

        return $request;
    }

    protected function mockUsageTracker(): void
    {
        $tracker = Mockery::mock(UsageTracker::class);
        $tracker->shouldReceive('isAtLimit')->andReturn(false);
        $this->app->instance(UsageTracker::class, $tracker);
    }

    #[Test]
    public function it_ignores_platform_only_fields_for_tenant_on_store()
    {
        $this->mockUsageTracker();

        $user = $this->makeUser('Tenant Owner');
        $request = $this->requestAs($user, [
            'name' => 'Support Agent',
            'role' => 'Support',
            'ai_model' => 'gpt-4o',
            'temperature' => 1.5,
        ]);

        app(AiEmployeeController::class)->store($request);

        $employee = AiEmployee::first();
        $this->assertNotNull($employee);
        $this->assertSame('deepseek-chat', $employee->ai_model);
        $this->assertEqualsWithDelta(0.7, $employee->temperature, 0.0001);
    }

    #[Test]
    public function it_allows_platform_owner_to_set_platform_fields_on_store()
    {
        $this->mockUsageTracker();

        $user = $this->makeUser('Platform Owner');
        $request = $this->requestAs($user, [
            'name' => 'Sales Representative',
            'role' => 'Sales',
            'ai_model' => 'gpt-4o',
            'temperature' => 1.5,
        ]);

        app(AiEmployeeController::class)->store($request);

        $employee = AiEmployee::first();
        $this->assertNotNull($employee);
        $this->assertSame('gpt-4o', $employee->ai_model);
        $this->assertEqualsWithDelta(1.5, $employee->temperature, 0.0001);
    }

    #[Test]
    public function it_preserves_platform_fields_for_tenant_on_update()
    {
        $employee = AiEmployee::create([
            'organization_id' => $this->organization->id,
            'name' => 'Existing Agent',
            'role' => 'Support',
            'ai_model' => 'gpt-4o',
            'temperature' => 1.5,
        ]);

        $user = $this->makeUser('Tenant Owner');
        $request = $this->requestAs($user, [
            'name' => 'Existing Agent',
            'role' => 'Support',
            'ai_model' => 'deepseek-reasoner',
            'temperature' => 0.1,
        ]);

        app(AiEmployeeController::class)->update($request, $employee);

        $employee->refresh();
        $this->assertSame('gpt-4o', $employee->ai_model);
        $this->assertEqualsWithDelta(1.5, $employee->temperature, 0.0001);
    }

    #[Test]
    public function it_allows_platform_owner_to_update_platform_fields()
    {
        $employee = AiEmployee::create([
            'organization_id' => $this->organization->id,
            'name' => 'Existing Agent',
            'role' => 'Support',
            'ai_model' => 'gpt-4o',
            'temperature' => 1.5,
        ]);

        $user = $this->makeUser('Platform Owner');
        $request = $this->requestAs($user, [
            'name' => 'Existing Agent',
            'role' => 'Support',
            'ai_model' => 'deepseek-reasoner',
            'temperature' => 0.3,
        ]);

        app(AiEmployeeController::class)->update($request, $employee);

        $employee->refresh();
        $this->assertSame('deepseek-reasoner', $employee->ai_model);
        $this->assertEqualsWithDelta(0.3, $employee->temperature, 0.0001);
    }
}
