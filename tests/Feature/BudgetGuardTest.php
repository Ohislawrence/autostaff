<?php

namespace Tests\Feature;

use App\Ai\Orchestrator\AiOrchestrator;
use App\Ai\Providers\AiProviderInterface;
use App\Ai\Providers\AiResponse;
use App\Models\AiEmployee;
use App\Models\AiRun;
use App\Models\Customer;
use App\Models\Organization;
use App\Services\ConversationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BudgetGuardTest extends TestCase
{
    use RefreshDatabase;

    protected Organization $organization;
    protected AiEmployee $aiEmployee;
    protected Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organization = Organization::create([
            'name' => 'Budget Guard Org',
            'slug' => 'budget-guard-org',
            'onboarding_completed' => true,
            'monthly_ai_budget_cents' => 1000, // $10.00
        ]);

        app()->instance('current_organization_id', $this->organization->id);

        $this->aiEmployee = new AiEmployee([
            'organization_id' => $this->organization->id,
            'name' => 'Budget AI',
            'role' => 'Support Agent',
            'is_active' => true,
        ]);
        $this->aiEmployee->save();

        $this->customer = new Customer([
            'organization_id' => $this->organization->id,
            'first_name' => 'Alex',
            'email' => 'alex@example.com',
        ]);
        $this->customer->save();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test] public function it_blocks_ai_when_budget_is_exceeded()
    {
        AiRun::create([
            'ai_employee_id' => $this->aiEmployee->id,
            'estimated_cost' => 15.00, // exceeds the $10 budget
            'status' => 'success',
        ]);

        $mockProvider = Mockery::mock(AiProviderInterface::class);
        $mockProvider->shouldNotReceive('chat');

        $this->app->instance(AiProviderInterface::class, $mockProvider);

        $orchestrator = app(AiOrchestrator::class);
        $conversationService = app(ConversationService::class);

        $conversation = $conversationService->findOrCreateConversation(
            $this->organization,
            $this->aiEmployee,
            $this->customer,
            'web_chat'
        );

        $result = $orchestrator->processIncomingMessage(
            $this->aiEmployee,
            $conversation,
            $this->customer,
            'Hello',
            ['channel' => 'web_chat']
        );

        $this->assertTrue($result['success']);
        $this->assertTrue($result['budget_blocked']);
        $this->assertStringContainsString('limited mode', $result['response']);
    }

    #[Test] public function it_allows_ai_when_under_budget()
    {
        $mockProvider = Mockery::mock(AiProviderInterface::class);
        $mockProvider->shouldReceive('chat')
            ->once()
            ->andReturn(new AiResponse(
                content: 'Hello!',
                model: 'deepseek-chat',
                metadata: ['provider' => 'deepseek'],
            ));

        $this->app->instance(AiProviderInterface::class, $mockProvider);

        $orchestrator = app(AiOrchestrator::class);
        $conversationService = app(ConversationService::class);

        $conversation = $conversationService->findOrCreateConversation(
            $this->organization,
            $this->aiEmployee,
            $this->customer,
            'web_chat'
        );

        $result = $orchestrator->processIncomingMessage(
            $this->aiEmployee,
            $conversation,
            $this->customer,
            'Hello',
            ['channel' => 'web_chat']
        );

        $this->assertTrue($result['success']);
        $this->assertFalse($result['budget_blocked'] ?? false);
        $this->assertStringContainsString('Hello', $result['response']);
    }
}
