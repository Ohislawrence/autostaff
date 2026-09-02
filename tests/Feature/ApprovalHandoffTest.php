<?php

namespace Tests\Feature;

use App\Ai\Tools\ToolRegistry;
use App\Models\AiEmployee;
use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Organization;
use App\Models\Tool;
use App\Services\ConversationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ApprovalHandoffTest extends TestCase
{
    use RefreshDatabase;

    protected Organization $organization;
    protected AiEmployee $aiEmployee;
    protected Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organization = Organization::create([
            'name' => 'Approval Org',
            'slug' => 'approval-org',
            'currency' => 'NGN',
            'onboarding_completed' => true,
        ]);

        app()->instance('current_organization_id', $this->organization->id);

        // Sync tools to DB so lookups resolve.
        app(ToolRegistry::class)->syncToDatabase();

        $this->aiEmployee = new AiEmployee([
            'organization_id' => $this->organization->id,
            'name' => 'Sales AI',
            'role' => 'Sales',
            'is_active' => true,
        ]);
        $this->aiEmployee->save();

        $this->customer = new Customer([
            'organization_id' => $this->organization->id,
            'first_name' => 'Approval',
            'last_name' => 'Customer',
            'email' => 'approval@example.com',
        ]);
        $this->customer->save();
    }

    protected function makeConversation(): Conversation
    {
        return app(ConversationService::class)->findOrCreateConversation(
            $this->organization,
            $this->aiEmployee,
            $this->customer,
            'web_chat'
        );
    }

    #[Test] public function handoff_service_builds_structured_payload()
    {
        $conversation = $this->makeConversation();
        $conversation->messages()->create([
            'organization_id' => $this->organization->id,
            'type' => 'incoming',
            'content' => 'I want to talk to a human',
        ]);

        $payload = app(\App\Services\HandoffService::class)->build(
            $conversation,
            'Customer requested human'
        );

        $this->assertArrayHasKey('customer', $payload);
        $this->assertArrayHasKey('related_orders', $payload);
        $this->assertArrayHasKey('ai_actions', $payload);
        $this->assertSame('Customer requested human', $payload['reason']);
        $this->assertSame('human_required', $conversation->fresh()->status);
    }

    #[Test] public function escalation_persists_handoff_and_status()
    {
        $conversation = $this->makeConversation();
        app(ConversationService::class)->escalateToHuman($conversation, 'Customer frustrated');

        $fresh = $conversation->fresh();
        $this->assertSame('human_required', $fresh->status);
        $this->assertArrayHasKey('reason', $fresh->metadata['handoff']);
    }
}