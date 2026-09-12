<?php

namespace Tests\Feature;

use App\Http\Controllers\AiEmployeeController;
use App\Models\AiEmployee;
use App\Models\Organization;
use App\Models\ProspectingCampaign;
use App\Models\User;
use App\Services\Billing\UsageTracker;
use App\Services\Prospecting\CampaignCreator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EmployeeCampaignWizardTest extends TestCase
{
    use RefreshDatabase;

    protected Organization $organization;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organization = Organization::create([
            'name' => 'Wizard Org',
            'slug' => 'wizard-org',
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
            'email' => strtolower(str_replace(' ', '.', $role)).'-wizard@example.com',
            'password' => bcrypt('password'),
        ]);
        $user->assignRole($role);

        return $user;
    }

    protected function mockUsageTracker(): void
    {
        $tracker = Mockery::mock(UsageTracker::class);
        $tracker->shouldReceive('isAtLimit')->andReturn(false);
        $this->app->instance(UsageTracker::class, $tracker);
    }

    #[Test]
    public function store_creates_a_linked_campaign_when_campaign_name_is_provided(): void
    {
        $this->mockUsageTracker();

        $request = new Request([
            'name' => 'SDR',
            'role' => 'Sales Development Rep',
            'campaign' => [
                'name' => 'Websites for dental clinics',
                'offer' => 'Websites for dental clinics',
                'icp_industry' => 'Healthcare, Dental',
                'icp_company_size' => '1-10',
                'icp_geography' => 'United States',
                'icp_job_titles' => 'Owner, Dentist',
                'daily_limit' => 25,
                'postal_address' => '123 Main St, Austin, TX',
                'compliance_regions' => 'us, uk',
            ],
        ]);
        $request->setUserResolver(fn () => $this->makeUser('Tenant Owner'));

        app(AiEmployeeController::class)->store($request);

        $employee = AiEmployee::first();
        $this->assertNotNull($employee);

        $campaign = ProspectingCampaign::first();
        $this->assertNotNull($campaign);
        $this->assertSame($employee->id, $campaign->ai_employee_id);
        $this->assertSame('Websites for dental clinics', $campaign->name);
        $this->assertSame(['Healthcare', 'Dental'], $campaign->icp['industry']);
        $this->assertSame('1-10', $campaign->icp['company_size']);
        $this->assertSame('draft', $campaign->status);
    }

    #[Test]
    public function store_does_not_create_a_campaign_when_skipped(): void
    {
        $this->mockUsageTracker();

        $request = new Request([
            'name' => 'Support Agent',
            'role' => 'Support',
            'campaign' => ['name' => '', 'offer' => ''],
        ]);
        $request->setUserResolver(fn () => $this->makeUser('Tenant Owner'));

        app(AiEmployeeController::class)->store($request);

        $this->assertSame(1, AiEmployee::count());
        $this->assertSame(0, ProspectingCampaign::count());
    }

    #[Test]
    public function campaign_creator_builds_icp_and_sourcing_from_input(): void
    {
        $employee = AiEmployee::create(['name' => 'SDR', 'role' => 'Sales Development Rep', 'is_active' => true]);

        $campaign = app(CampaignCreator::class)->create([
            'name' => 'Test campaign',
            'offer' => 'Websites',
            'icp_industry' => 'Tech, SaaS',
            'icp_geography' => 'UK, Europe',
            'icp_job_titles' => 'Founder',
            'compliance_regions' => 'us, uk',
            'daily_limit' => 30,
        ], $this->organization, $employee);

        $this->assertSame($this->organization->id, $campaign->organization_id);
        $this->assertSame($employee->id, $campaign->ai_employee_id);
        $this->assertSame(['Tech', 'SaaS'], $campaign->icp['industry']);
        $this->assertSame(['UK', 'Europe'], $campaign->icp['geography']);
        $this->assertSame(['us', 'uk'], $campaign->compliance_regions);
        $this->assertSame(30, $campaign->daily_limit);
        $this->assertTrue($campaign->sourcing_rules['allow_ai_generated']);
        $this->assertSame('draft', $campaign->status);
    }
}
