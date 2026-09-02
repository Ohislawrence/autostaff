<?php

namespace Tests\Feature;

use App\Ai\Orchestrator\AiOrchestrator;
use App\Ai\Providers\AiProviderInterface;
use App\Ai\Providers\AiResponse;
use App\Models\AiEmployee;
use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Organization;
use App\Models\Message;
use App\Models\User;
use App\Services\ConversationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ChatPipelineTest extends TestCase
{
    use RefreshDatabase;

    protected Organization $organization;
    protected AiEmployee $aiEmployee;
    protected Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organization = Organization::create([
            'name' => 'Test Org',
            'slug' => 'test-org',
            'onboarding_completed' => true,
        ]);

        // Bypass TenantAware auto-population by directly setting org_id
        $this->aiEmployee = new AiEmployee([
            'organization_id' => $this->organization->id,
            'name' => 'Test AI',
            'role' => 'Support Agent',
            'is_active' => true,
        ]);
        $this->aiEmployee->save();

        $this->customer = new Customer([
            'organization_id' => $this->organization->id,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
        ]);
        $this->customer->save();

        app()->instance('current_organization_id', $this->organization->id);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test] public function conversation_service_creates_customer()
    {
        $service = app(ConversationService::class);

        $customer = $service->findOrCreateCustomer($this->organization, [
            'first_name' => 'Jane',
            'email' => 'jane@example.com',
            'channel' => 'web_chat',
        ]);

        $this->assertNotNull($customer);
        $this->assertEquals('Jane', $customer->first_name);
        $this->assertEquals('jane@example.com', $customer->email);
    }

    #[Test] public function conversation_service_returns_existing_customer()
    {
        $service = app(ConversationService::class);

        $customer1 = $service->findOrCreateCustomer($this->organization, [
            'first_name' => 'John',
            'email' => 'john@example.com',
            'channel' => 'web_chat',
        ]);

        $customer2 = $service->findOrCreateCustomer($this->organization, [
            'first_name' => 'John Updated',
            'email' => 'john@example.com',
            'channel' => 'web_chat',
        ]);

        $this->assertEquals($customer1->id, $customer2->id);
        $this->assertEquals(1, Customer::count());
    }

    #[Test] public function conversation_service_creates_conversation()
    {
        $service = app(ConversationService::class);

        $conversation = $service->findOrCreateConversation(
            $this->organization,
            $this->aiEmployee,
            $this->customer,
            'web_chat'
        );

        $this->assertNotNull($conversation);
        $this->assertEquals('ai_handling', $conversation->status);
        $this->assertEquals($this->customer->id, $conversation->customer_id);
    }

    #[Test] public function conversation_service_creates_messages()
    {
        $service = app(ConversationService::class);

        $conversation = $service->findOrCreateConversation(
            $this->organization,
            $this->aiEmployee,
            $this->customer,
            'web_chat'
        );

        $incoming = $service->createIncomingMessage($conversation, $this->customer, 'Hello, I need help');
        $this->assertEquals('incoming', $incoming->type);
        $this->assertEquals('Hello, I need help', $incoming->content);

        $aiResponse = $service->createAiResponse($conversation, $this->aiEmployee, 'How can I help?');
        $this->assertEquals('ai_response', $aiResponse->type);
        $this->assertEquals('How can I help?', $aiResponse->content);

        $this->assertEquals(2, $conversation->messages()->count());
    }

    #[Test] public function conversation_service_detects_escalation_keywords()
    {
        $service = app(ConversationService::class);

        $conversation = $service->findOrCreateConversation(
            $this->organization,
            $this->aiEmployee,
            $this->customer,
            'web_chat'
        );

        // Default shouldn't escalate
        $this->assertFalse($service->shouldEscalate($conversation, $this->aiEmployee, 'What are your hours?'));

        // Explicit human request should escalate
        $this->assertTrue($service->shouldEscalate($conversation, $this->aiEmployee, 'I want to talk to a human agent'));
        $this->assertTrue($service->shouldEscalate($conversation, $this->aiEmployee, 'Let me speak to a real person'));
    }

    #[Test] public function conversation_service_resolves_and_closes()
    {
        $service = app(ConversationService::class);

        $conversation = $service->findOrCreateConversation(
            $this->organization,
            $this->aiEmployee,
            $this->customer,
            'web_chat'
        );

        $service->resolve($conversation);
        $this->assertEquals('resolved', $conversation->fresh()->status);
        $this->assertNotNull($conversation->fresh()->resolved_at);

        $service->close($conversation);
        $this->assertEquals('closed', $conversation->fresh()->status);
    }

    #[Test] public function ai_orchestrator_processes_message_with_mocked_provider()
    {
        // Mock AI provider to return a canned response
        $mockProvider = Mockery::mock(AiProviderInterface::class);
        $mockProvider->shouldReceive('chat')
            ->once()
            ->andReturn(new AiResponse(
                content: 'Hello! How can I assist you today?',
                model: 'deepseek-chat',
                inputTokens: 50,
                outputTokens: 10,
                latencyMs: 100,
                finishReason: 'stop',
                metadata: ['provider' => 'deepseek', 'id' => 'test-id'],
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
            'Hi there!',
            ['channel' => 'web_chat']
        );

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('Hello', $result['response']);

        // Verify AI response message was created
        $this->assertEquals(1, $conversation->messages()->where('type', 'ai_response')->count());
    }

    #[Test] public function ai_orchestrator_handles_provider_error_gracefully()
    {
        $mockProvider = Mockery::mock(AiProviderInterface::class);
        $mockProvider->shouldReceive('chat')
            ->once()
            ->andThrow(new \Exception('API timeout'));

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
            'Test message',
            ['channel' => 'web_chat']
        );

        $this->assertFalse($result['success']);
        $this->assertTrue($result['escalated']);
        $this->assertStringContainsString('API timeout', $result['error']);
    }

    protected function withoutTenancy(): void
    {
        // Remove global TenantScope for test setup
        // Models extend TenantAware which auto-applies the scope,
        // so we need to bypass it for test data creation
    }
}