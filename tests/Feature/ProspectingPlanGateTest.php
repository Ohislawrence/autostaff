<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Plan;
use App\Models\Subscription;
use App\Services\Prospecting\ProspectingPlanGate;
use Database\Seeders\PlansTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProspectingPlanGateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PlansTableSeeder::class);
    }

    #[Test] public function free_and_starter_do_not_allow_prospecting()
    {
        foreach (['free', 'starter'] as $slug) {
            $plan = Plan::where('slug', $slug)->first();

            $this->assertFalse($plan->allowsProspecting());
            $this->assertSame(
                ['max_campaigns' => 0, 'max_daily_prospects' => 0, 'max_daily_outreach' => 0],
                $plan->prospectingLimits()
            );
        }
    }

    #[Test] public function business_is_limited()
    {
        $plan = Plan::where('slug', 'business')->first();

        $this->assertTrue($plan->allowsProspecting());
        $this->assertSame(
            ['max_campaigns' => 2, 'max_daily_prospects' => 25, 'max_daily_outreach' => 50],
            $plan->prospectingLimits()
        );
    }

    #[Test] public function professional_is_more_open()
    {
        $plan = Plan::where('slug', 'professional')->first();

        $this->assertTrue($plan->allowsProspecting());
        $this->assertSame(
            ['max_campaigns' => 5, 'max_daily_prospects' => 100, 'max_daily_outreach' => 200],
            $plan->prospectingLimits()
        );
    }

    #[Test] public function enterprise_is_unlimited()
    {
        $plan = Plan::where('slug', 'enterprise')->first();

        $this->assertTrue($plan->allowsProspecting());
        $this->assertSame(
            ['max_campaigns' => null, 'max_daily_prospects' => null, 'max_daily_outreach' => null],
            $plan->prospectingLimits()
        );
    }

    #[Test] public function gate_resolves_limits_from_active_subscription()
    {
        $org = Organization::create(['name' => 'Prospecting Org', 'slug' => 'prospecting-org']);
        $business = Plan::where('slug', 'business')->first();

        Subscription::create([
            'organization_id' => $org->id,
            'plan_id' => $business->id,
            'status' => 'active',
        ]);

        $gate = app(ProspectingPlanGate::class);

        $this->assertTrue($gate->isAllowed($org->id));
        $this->assertSame(2, $gate->maxCampaigns($org->id));
        $this->assertSame(25, $gate->maxDailyProspects($org->id));
        $this->assertSame(50, $gate->maxDailyOutreach($org->id));
    }

    #[Test] public function gate_denies_org_without_paid_plan()
    {
        $org = Organization::create(['name' => 'Free Org', 'slug' => 'free-org']);

        $gate = app(ProspectingPlanGate::class);

        $this->assertFalse($gate->isAllowed($org->id));
        $this->assertSame(0, $gate->maxCampaigns($org->id));
    }
}
