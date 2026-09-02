<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Organization;
use App\Services\FollowupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FollowupServiceTest extends TestCase
{
    use RefreshDatabase;

    protected Organization $organization;
    protected Customer $customer;
    protected FollowupService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organization = Organization::create([
            'name' => 'Followup Org',
            'slug' => 'followup-org',
            'currency' => 'NGN',
            'timezone' => 'Africa/Lagos',
            'onboarding_completed' => true,
        ]);

        app()->instance('current_organization_id', $this->organization->id);

        $this->customer = new Customer([
            'organization_id' => $this->organization->id,
            'first_name' => 'Followup',
            'phone' => '+2348000000000',
        ]);
        $this->customer->save();

        $this->service = new FollowupService();
    }

    #[Test] public function it_schedules_a_followup_for_contactable_customer()
    {
        $result = $this->service->schedule(
            $this->organization,
            $this->customer,
            'Just checking in!',
            'whatsapp',
            24
        );

        $this->assertTrue($result['success']);
        $this->assertNotNull($result['scheduled_id']);
    }

    #[Test] public function it_refuses_when_too_many_pending_followups()
    {
        for ($i = 0; $i < 3; $i++) {
            $this->service->schedule($this->organization, $this->customer, "msg {$i}", 'whatsapp', 24);
        }

        $result = $this->service->schedule($this->organization, $this->customer, 'too many', 'whatsapp', 24);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Too many', $result['error']);
    }

    #[Test] public function it_blocks_dispatch_after_max_attempts()
    {
        $msg = (object) [
            'attempts' => 3,
            'max_attempts' => 3,
            'last_attempt_at' => null,
            'cooldown_minutes' => 1440,
            'business_hours_only' => false,
        ];

        $this->assertFalse($this->service->shouldDispatchNow($msg, $this->organization));
    }

    #[Test] public function it_blocks_dispatch_during_cooldown()
    {
        $msg = (object) [
            'attempts' => 1,
            'max_attempts' => 3,
            'last_attempt_at' => now()->subMinute()->toISOString(),
            'cooldown_minutes' => 1440,
            'business_hours_only' => false,
        ];

        $this->assertFalse($this->service->shouldDispatchNow($msg, $this->organization));
    }

    #[Test] public function it_allows_dispatch_when_guardrails_pass()
    {
        $msg = (object) [
            'attempts' => 0,
            'max_attempts' => 3,
            'last_attempt_at' => null,
            'cooldown_minutes' => 1440,
            'business_hours_only' => false,
        ];

        $this->assertTrue($this->service->shouldDispatchNow($msg, $this->organization));
    }
}