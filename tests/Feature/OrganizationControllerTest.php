<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class OrganizationControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('Tenant Owner', 'web');
    }

    #[Test]
    public function it_creates_a_new_organization_and_attaches_the_user_as_owner()
    {
        $user = User::create([
            'name' => 'Merchant',
            'email' => 'merchant@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->actingAs($user);

        $response = $this->post('/organizations', ['name' => 'Second Store']);

        $response->assertRedirect(route('dashboard'));

        $organization = Organization::where('name', 'Second Store')->first();
        $this->assertNotNull($organization);
        $this->assertTrue($organization->onboarding_completed);
        $this->assertTrue(
            $organization->users()->wherePivot('is_owner', true)->where('user_id', $user->id)->exists()
        );
        $this->assertSame($organization->id, session('current_organization_id'));
        $this->assertTrue($user->fresh()->hasRole('Tenant Owner'));
    }
}
