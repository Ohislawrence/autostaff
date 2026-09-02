<?php

namespace Tests\Feature;

use App\Ai\Tools\ToolExecutor;
use App\Mcp\McpToolHandler;
use App\Models\AiEmployee;
use App\Models\Conversation;
use App\Models\Customer;
use App\Models\McpConnection;
use App\Models\Organization;
use App\Models\Tool;
use App\Services\Mcp\McpConnectionManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class McpToolExecutorTest extends TestCase
{
    use RefreshDatabase;

    protected Organization $organization;
    protected AiEmployee $employee;
    protected Customer $customer;
    protected McpConnection $connection;
    protected Tool $tool;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organization = Organization::create([
            'name' => 'MCP Exec Org',
            'slug' => 'mcp-exec-org',
            'onboarding_completed' => true,
        ]);
        app()->instance('current_organization_id', $this->organization->id);

        $this->employee = new AiEmployee([
            'organization_id' => $this->organization->id,
            'name' => 'MCP AI',
            'role' => 'Support',
            'is_active' => true,
        ]);
        $this->employee->save();

        $this->customer = new Customer([
            'organization_id' => $this->organization->id,
            'first_name' => 'Sam',
            'email' => 'sam@example.com',
        ]);
        $this->customer->save();

        $this->connection = (new McpConnectionManager())->store($this->organization->id, [
            'provider' => 'gmail',
            'endpoint' => 'https://mcp.example.com',
            'credentials' => ['access_token' => 'secret'],
        ]);

        $this->tool = Tool::create([
            'identifier' => "mcp_{$this->connection->id}_send_email",
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
                'mcp_connection_id' => $this->connection->id,
                'mcp_tool' => 'send_email',
            ],
            'is_active' => true,
        ]);

        $this->employee->tools()->attach($this->tool->id, ['is_allowed' => true]);
    }

    #[Test] public function it_executes_an_mcp_tool_through_the_full_executor()
    {
        Http::fake([
            'https://mcp.example.com*' => Http::sequence()
                ->push(['jsonrpc' => '2.0', 'id' => 1, 'result' => ['serverInfo' => ['name' => 'demo']]])
                ->push(['jsonrpc' => '2.0', 'id' => 2, 'result' => ['content' => [['type' => 'text', 'text' => 'Email sent']], 'isError' => false]]),
        ]);

        $conversation = new Conversation([
            'organization_id' => $this->organization->id,
            'customer_id' => $this->customer->id,
            'ai_employee_id' => $this->employee->id,
            'channel' => 'web_chat',
            'status' => 'ai_handling',
            'metadata' => [],
        ]);
        $conversation->save();

        $executor = app(ToolExecutor::class);

        $result = $executor->execute(
            $this->employee,
            $conversation,
            $this->tool->identifier,
            ['to' => 'x@example.com']
        );

        $this->assertSame('success', $result['status']);
        $this->assertTrue($result['verified']);
        $this->assertSame('Email sent', $result['message']);
    }
}
