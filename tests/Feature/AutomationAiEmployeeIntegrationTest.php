<?php

namespace Tests\Feature;

use App\Ai\Orchestrator\AiOrchestrator;
use App\Ai\Tools\BuiltIn\CreateLeadTool;
use App\Ai\Tools\BuiltIn\CreateOrderTool;
use App\Models\AiEmployee;
use App\Models\Automation;
use App\Models\AutomationRun;
use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\Order;
use App\Models\Organization;
use App\Models\Product;
use App\Services\Automation\AutomationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AutomationAiEmployeeIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected Organization $organization;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organization = Organization::create([
            'name' => 'Automation AI Org',
            'slug' => 'automation-ai-org',
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

    #[Test]
    public function it_invokes_an_ai_employee_from_an_automation()
    {
        $employee = AiEmployee::create([
            'name' => 'Sales Bot',
            'role' => 'Sales',
            'is_active' => true,
        ]);

        $customer = Customer::create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
        ]);

        Automation::create([
            'name' => 'Invoke Sales Bot',
            'trigger_type' => 'new_lead',
            'is_active' => true,
            'actions' => [[
                'type' => 'invoke_ai_employee',
                'config' => [
                    'ai_employee_id' => $employee->id,
                    'prompt' => 'Follow up with {{customer_name}}',
                    'create_conversation' => true,
                ],
            ]],
        ]);

        $orchestrator = Mockery::mock(AiOrchestrator::class);
        $orchestrator->shouldReceive('processIncomingMessage')
            ->once()
            ->andReturn([
                'success' => true,
                'response' => 'Hello John',
                'tool_calls' => [],
                'escalated' => false,
            ]);
        $this->app->instance(AiOrchestrator::class, $orchestrator);

        $results = app(AutomationService::class)->process('new_lead', [
            'customer_id' => $customer->id,
            'customer_name' => 'John Doe',
        ], $this->organization->id);

        $this->assertNotEmpty($results);
        $this->assertSame('completed', $results[0]['status']);
        $this->assertSame(1, $results[0]['workflow_depth']);
        $this->assertSame(1, AutomationRun::count());
        $this->assertSame(1, Conversation::count());
    }

    #[Test]
    public function it_fires_new_lead_trigger_when_ai_creates_a_lead()
    {
        $customer = Customer::create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
        ]);

        $automationService = Mockery::mock(AutomationService::class);
        $automationService->shouldReceive('triggerOnLeadCreated')
            ->once()
            ->with(Mockery::type(Lead::class));
        $this->app->instance(AutomationService::class, $automationService);

        $result = app(CreateLeadTool::class)->execute([
            'customer_id' => $customer->id,
            'source' => 'ai',
            'product_interest' => 'Widgets',
        ]);

        $this->assertTrue($result['success']);
        $this->assertSame(1, Lead::count());
    }

    #[Test]
    public function it_fires_order_created_trigger_when_ai_creates_an_order()
    {
        $customer = Customer::create([
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => 'jane@example.com',
        ]);

        $product = Product::create([
            'name' => 'Widget',
            'sku' => 'WID-1',
            'price' => 19.99,
        ]);

        $automationService = Mockery::mock(AutomationService::class);
        $automationService->shouldReceive('triggerOnOrderCreated')
            ->once()
            ->with(Mockery::type(Order::class));
        $this->app->instance(AutomationService::class, $automationService);

        $result = app(CreateOrderTool::class)->execute([
            'customer_id' => $customer->id,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 2],
            ],
        ]);

        $this->assertTrue($result['success']);
        $this->assertSame(1, Order::count());
    }

    #[Test]
    public function it_stops_automations_beyond_the_maximum_workflow_depth()
    {
        $automation = Automation::create([
            'name' => 'Depth Guard',
            'trigger_type' => 'new_lead',
            'is_active' => true,
            'actions' => [['type' => 'create_task', 'config' => ['title' => 'Should not run']]],
        ]);

        // Simulate being at the maximum allowed depth already.
        app()->instance('automation_workflow_depth', 3);

        $result = app(AutomationService::class)->execute($automation, ['lead_id' => 1]);

        $this->assertSame('depth_exceeded', $result['status']);
        $this->assertSame(0, AutomationRun::count());
    }
}
