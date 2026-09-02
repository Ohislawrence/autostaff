<?php

namespace Tests\Feature;

use App\Ai\Workflow\PolicyService;
use App\Ai\Workflow\WorkflowStateMachine;
use App\Models\AiEmployee;
use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WorkflowStateMachineTest extends TestCase
{
    use RefreshDatabase;

    protected Organization $organization;
    protected AiEmployee $aiEmployee;
    protected Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organization = Organization::create([
            'name' => 'Workflow Org',
            'slug' => 'workflow-org',
            'currency' => 'NGN',
            'onboarding_completed' => true,
        ]);

        // Bind tenant context BEFORE creating tenant-scoped models so the
        // TenantAware trait auto-populates organization_id.
        app()->instance('current_organization_id', $this->organization->id);

        $this->aiEmployee = new AiEmployee([
            'organization_id' => $this->organization->id,
            'name' => 'Sales AI',
            'role' => 'Sales',
            'is_active' => true,
        ]);
        $this->aiEmployee->save();

        $this->customer = new Customer([
            'organization_id' => $this->organization->id,
            'first_name' => 'Jane',
            'email' => 'jane@example.com',
        ]);
        $this->customer->save();
    }

    protected function makeConversation(array $metadata = []): Conversation
    {
        $conversation = new Conversation([
            'organization_id' => $this->organization->id,
            'customer_id' => $this->customer->id,
            'ai_employee_id' => $this->aiEmployee->id,
            'channel' => 'web_chat',
            'status' => 'ai_handling',
            'metadata' => $metadata,
        ]);
        $conversation->save();

        return $conversation;
    }

    #[Test] public function it_allows_read_only_tools_from_any_state()
    {
        $state = app(WorkflowStateMachine::class);
        $conversation = $this->makeConversation();

        $decision = $state->authorizeTool($conversation, 'search_products');

        $this->assertTrue($decision['allowed']);
        $this->assertTrue($decision['read_only']);
    }

    #[Test] public function it_allows_quotation_from_idle_state()
    {
        $state = app(WorkflowStateMachine::class);
        $conversation = $this->makeConversation();

        $decision = $state->authorizeTool($conversation, 'generate_quotation');

        $this->assertTrue($decision['allowed']);
        $this->assertSame('idle', $decision['from']);
        $this->assertSame('quotation_sent', $decision['to']);
    }

    #[Test] public function it_denies_payment_before_quotation()
    {
        $state = app(WorkflowStateMachine::class);
        $conversation = $this->makeConversation(); // still idle

        $decision = $state->authorizeTool($conversation, 'record_payment');

        $this->assertFalse($decision['allowed']);
    }

    #[Test] public function it_advances_state_to_quotation_sent_on_success()
    {
        $state = app(WorkflowStateMachine::class);
        $conversation = $this->makeConversation();

        $state->recordTransition($conversation, 'generate_quotation');

        $this->assertSame('quotation_sent', $state->current($conversation)['state']);
    }

    #[Test] public function policy_requires_approval_for_financial_tools()
    {
        $policy = app(PolicyService::class);
        $conversation = $this->makeConversation();

        $decision = $policy->evaluate($this->aiEmployee, $conversation, 'record_payment', []);

        $this->assertTrue($decision['requires_approval']);
        $this->assertFalse($decision['allowed']);
    }

    #[Test] public function policy_blocks_discount_over_limit()
    {
        $policy = app(PolicyService::class);
        $conversation = $this->makeConversation();

        $decision = $policy->evaluate(
            $this->aiEmployee,
            $conversation,
            'generate_quotation',
            ['discount_percent' => 25]
        );

        $this->assertTrue($decision['requires_approval']);
        $this->assertFalse($decision['allowed']);
    }
}