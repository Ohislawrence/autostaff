<?php

namespace Tests\Feature;

use App\Ai\Tools\ToolExecutor;
use App\Mcp\McpToolHandler;
use App\Models\AiEmployee;
use App\Models\AiRun;
use App\Models\Conversation;
use App\Models\Customer;
use App\Models\McpConnection;
use App\Models\Organization;
use App\Models\Tool;
use App\Models\ToolExecution;
use App\Services\Guardrails\CostGuardService;
use App\Services\Mcp\McpConnectionManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ToolCostAttributionTest extends TestCase
{
    use RefreshDatabase;

    protected Organization $organization;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organization = Organization::create([
            'name' => 'Cost Org',
            'slug' => 'cost-org',
            'onboarding_completed' => true,
            'monthly_ai_budget_cents' => 1000, // $10.00
        ]);
        app()->instance('current_organization_id', $this->organization->id);
    }

    protected function seedTool(): Tool
    {
        return Tool::create([
            'identifier' => 'seed_tool',
            'name' => 'Seed Tool',
            'description' => 'A test tool',
            'input_schema' => ['type' => 'object'],
            'output_schema' => ['type' => 'object'],
            'handler_class' => McpToolHandler::class,
            'requires_confirmation' => false,
            'category' => 'test',
            'is_custom' => false,
            'organization_id' => null,
            'is_active' => true,
        ]);
    }

    #[Test] public function it_counts_tool_execution_cost_toward_budget()
    {
        $tool = $this->seedTool();

        ToolExecution::create([
            'tool_id' => $tool->id,
            'status' => 'success',
            'input_parameters' => [],
            'estimated_cost' => 12.00,
        ]);

        $guard = app(CostGuardService::class);

        $this->assertFalse($guard->checkBudget($this->organization->id));
        $this->assertSame(12.0, $guard->getCurrentMonthSpend($this->organization->id));
    }

    #[Test] public function it_combines_ai_runs_and_tool_executions_in_spend()
    {
        AiRun::create([
            'estimated_cost' => 5.00,
            'status' => 'success',
        ]);

        $tool = $this->seedTool();
        ToolExecution::create([
            'tool_id' => $tool->id,
            'status' => 'success',
            'input_parameters' => [],
            'estimated_cost' => 3.00,
        ]);

        $guard = app(CostGuardService::class);

        $this->assertSame(8.0, $guard->getCurrentMonthSpend($this->organization->id));
        $this->assertTrue($guard->checkBudget($this->organization->id));
    }

    #[Test] public function it_records_mcp_cost_on_tool_execution()
    {
        $connection = (new McpConnectionManager())->store($this->organization->id, [
            'provider' => 'gmail',
            'endpoint' => 'https://mcp.example.com',
            'credentials' => ['access_token' => 'secret'],
            'pricing' => ['per_call' => 0.005],
        ]);

        $employee = new AiEmployee([
            'organization_id' => $this->organization->id,
            'name' => 'MCP AI',
            'role' => 'Support',
            'is_active' => true,
        ]);
        $employee->save();

        $customer = new Customer([
            'organization_id' => $this->organization->id,
            'first_name' => 'Sam',
            'email' => 'sam@example.com',
        ]);
        $customer->save();

        $tool = Tool::create([
            'identifier' => "mcp_{$connection->id}_send_email",
            'name' => 'send_email',
            'description' => 'Send an email',
            'input_schema' => ['type' => 'object'],
            'output_schema' => ['type' => 'object'],
            'handler_class' => McpToolHandler::class,
            'requires_confirmation' => false,
            'category' => 'external',
            'is_custom' => true,
            'organization_id' => $this->organization->id,
            'custom_config' => [
                'mcp_connection_id' => $connection->id,
                'mcp_tool' => 'send_email',
            ],
            'is_active' => true,
        ]);
        $employee->tools()->attach($tool->id, ['is_allowed' => true]);

        Http::fake([
            'https://mcp.example.com*' => Http::sequence()
                ->push(['jsonrpc' => '2.0', 'id' => 1, 'result' => ['serverInfo' => ['name' => 'demo']]])
                ->push(['jsonrpc' => '2.0', 'id' => 2, 'result' => ['content' => [['type' => 'text', 'text' => 'Email sent']], 'isError' => false]]),
        ]);

        $conversation = new Conversation([
            'organization_id' => $this->organization->id,
            'customer_id' => $customer->id,
            'ai_employee_id' => $employee->id,
            'channel' => 'web_chat',
            'status' => 'ai_handling',
            'metadata' => [],
        ]);
        $conversation->save();

        $executor = app(ToolExecutor::class);
        $result = $executor->execute($employee, $conversation, $tool->identifier, ['to' => 'x@example.com']);

        $this->assertSame('success', $result['status']);

        $execution = ToolExecution::where('tool_id', $tool->id)->first();
        $this->assertNotNull($execution);
        $this->assertSame(0.005, (float) $execution->estimated_cost);
    }
}
