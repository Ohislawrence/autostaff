<?php

namespace Tests\Feature;

use App\Models\AiRun;
use App\Models\Organization;
use App\Services\Guardrails\CostGuardService;
use App\Services\Guardrails\FallbackManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CostGuardServiceTest extends TestCase
{
    use RefreshDatabase;

    protected Organization $organization;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organization = Organization::create([
            'name' => 'Budget Org',
            'slug' => 'budget-org',
            'currency' => 'NGN',
            'onboarding_completed' => true,
            'monthly_ai_budget_cents' => 1000, // $10.00
        ]);

        app()->instance('current_organization_id', $this->organization->id);
    }

    protected function seedSpend(float $dollars): AiRun
    {
        return AiRun::create([
            'estimated_cost' => $dollars,
            'status' => 'success',
        ]);
    }

    #[Test] public function it_allows_when_no_budget_is_set()
    {
        $this->organization->update(['monthly_ai_budget_cents' => null]);

        $guard = app(CostGuardService::class);

        $this->assertTrue($guard->checkBudget($this->organization->id));
    }

    #[Test] public function it_allows_when_under_budget()
    {
        $this->seedSpend(5.00);

        $guard = app(CostGuardService::class);

        $this->assertTrue($guard->checkBudget($this->organization->id));
    }

    #[Test] public function it_blocks_when_budget_is_exceeded()
    {
        $this->seedSpend(12.00);

        $guard = app(CostGuardService::class);

        $this->assertFalse($guard->checkBudget($this->organization->id));
    }

    #[Test] public function it_enters_degraded_mode_at_ninety_percent()
    {
        $this->seedSpend(9.50); // 95% of $10.00

        $guard = app(CostGuardService::class);

        $this->assertTrue($guard->isDegradedMode($this->organization->id));
    }

    #[Test] public function it_does_not_enter_degraded_mode_below_threshold()
    {
        $this->seedSpend(5.00);

        $guard = app(CostGuardService::class);

        $this->assertFalse($guard->isDegradedMode($this->organization->id));
    }

    #[Test] public function fallback_manager_returns_canned_messages()
    {
        $fallback = app(FallbackManager::class);

        $this->assertNotEmpty($fallback->getCannedResponse('budget'));
        $this->assertNotEmpty($fallback->getCannedResponse('timeout'));
        $this->assertNotEmpty($fallback->getCannedResponse('error'));
    }
}
