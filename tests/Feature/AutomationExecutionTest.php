<?php

namespace Tests\Feature;

use App\Models\Automation;
use App\Models\Conversation;
use App\Models\Lead;
use App\Models\Organization;
use App\Models\Task;
use App\Services\Automation\AutomationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AutomationExecutionTest extends TestCase
{
    use RefreshDatabase;

    protected Organization $organization;
    protected AutomationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organization = Organization::create([
            'name' => 'Test Org',
            'slug' => 'test-org',
            'onboarding_completed' => true,
        ]);

        app()->instance('current_organization_id', $this->organization->id);
        $this->service = app(AutomationService::class);
    }

    #[Test] public function condition_evaluation_passes_with_empty_conditions()
    {
        $result = $this->invokeMethod(
            $this->service,
            'evaluateConditions',
            [[], ['customer_name' => 'John']]
        );

        $this->assertTrue($result['passed']);
    }

    #[Test] public function condition_equals()
    {
        $result = $this->invokeMethod($this->service, 'evaluateCondition', ['hello', 'equals', 'hello']);
        $this->assertTrue($result);
        $result = $this->invokeMethod($this->service, 'evaluateCondition', ['hello', 'equals', 'world']);
        $this->assertFalse($result);
    }

    #[Test] public function condition_contains()
    {
        $this->assertTrue($this->invokeMethod($this->service, 'evaluateCondition', ['this is a great product', 'contains', 'great']));
        $this->assertFalse($this->invokeMethod($this->service, 'evaluateCondition', ['this is fine', 'contains', 'great']));
    }

    #[Test] public function condition_greater_than()
    {
        $this->assertTrue($this->invokeMethod($this->service, 'evaluateCondition', [100, 'greater_than', 50]));
        $this->assertFalse($this->invokeMethod($this->service, 'evaluateCondition', [10, 'greater_than', 50]));
    }

    #[Test] public function template_rendering_replaces_variables()
    {
        $result = $this->invokeMethod($this->service, 'renderTemplate', [
            'Hello {{name}}, your score is {{score}}',
            ['name' => 'John', 'score' => 95],
        ]);
        $this->assertEquals('Hello John, your score is 95', $result);
    }

    #[Test] public function action_send_message_creates_message_in_conversation()
    {
        $conversation = Conversation::create([
            'organization_id' => $this->organization->id,
            'customer_id' => 1,
            'channel' => 'web_chat',
            'status' => 'open',
        ]);

        $result = $this->invokeMethod($this->service, 'actionSendMessage', [
            ['message' => 'Hello {{customer_name}}!'],
            ['customer_name' => 'Jane', 'conversation_id' => $conversation->id],
        ]);

        $this->assertTrue($result['success']);
        $this->assertEquals(1, $conversation->messages()->count());
        $this->assertStringContainsString('Hello Jane', $conversation->messages()->first()->content);
    }

    #[Test] public function action_create_task_creates_task()
    {
        $result = $this->invokeMethod($this->service, 'actionCreateTask', [
            ['title' => 'Follow up with {{customer_name}}', 'description' => 'Call back', 'priority' => 'high'],
            ['customer_name' => 'Sarah'],
        ]);
        $this->assertTrue($result['success']);
        $this->assertEquals(1, Task::count());
        $this->assertEquals('Follow up with Sarah', Task::first()->title);
    }

    #[Test] public function action_notify_user_creates_notification()
    {
        $result = $this->invokeMethod($this->service, 'actionNotifyUser', [
            ['title' => 'New lead', 'body' => '{{customer_name}} signed up'],
            ['customer_name' => 'Mike'],
        ]);
        $this->assertTrue($result['success']);
        $this->assertEquals(1, \App\Models\Notification::count());
        $this->assertStringContainsString('Mike', \App\Models\Notification::first()->body);
    }

    #[Test] public function action_update_lead_changes_stage()
    {
        $lead = Lead::create([
            'organization_id' => $this->organization->id,
            'stage' => 'new',
            'source' => 'web_chat',
        ]);

        $result = $this->invokeMethod($this->service, 'actionUpdateLead', [
            ['stage' => 'qualified', 'lead_id' => $lead->id], [],
        ]);
        $this->assertTrue($result['success']);
        $this->assertEquals('qualified', $lead->fresh()->stage);
    }

    #[Test] public function action_assign_conversation()
    {
        $conversation = Conversation::create([
            'organization_id' => $this->organization->id,
            'customer_id' => 1,
            'channel' => 'web_chat',
            'status' => 'open',
        ]);

        $result = $this->invokeMethod($this->service, 'actionAssignConversation', [
            ['user_id' => 5, 'conversation_id' => $conversation->id], [],
        ]);
        $this->assertTrue($result['success']);
        $this->assertEquals('assigned', $conversation->fresh()->status);
        $this->assertEquals(5, $conversation->fresh()->assigned_user_id);
    }

    #[Test] public function action_webhook_calls_url()
    {
        Http::fake(['https://example.com/webhook' => Http::response(['ok' => true], 200)]);

        $result = $this->invokeMethod($this->service, 'actionCreateWebhook', [
            ['url' => 'https://example.com/webhook', 'event' => 'lead.created'],
            ['lead_id' => 1, 'customer_name' => 'John'],
        ]);
        $this->assertTrue($result['success']);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://example.com/webhook'
                && $request['event'] === 'lead.created'
                && $request['data']['customer_name'] === 'John';
        });
    }

    #[Test] public function action_send_email_dispatches_mail()
    {
        Mail::fake();

        $result = $this->invokeMethod($this->service, 'actionSendEmail', [
            ['to' => 'user@example.com', 'subject' => 'New Lead: {{customer_name}}', 'body' => 'Lead from {{customer_name}}'],
            ['customer_name' => 'Alice'],
        ]);
        $this->assertTrue($result['success']);

        Mail::assertSent(function ($mail) {
            return $mail->hasTo('user@example.com') && $mail->hasSubject('New Lead: Alice');
        });
    }

    #[Test] public function automation_full_execution_flow()
    {
        Automation::create([
            'organization_id' => $this->organization->id,
            'name' => 'Test Automation',
            'trigger_type' => 'new_lead',
            'conditions' => [['field' => 'lead_score', 'operator' => 'greater_than', 'value' => 50]],
            'actions' => [['type' => 'create_task', 'config' => ['title' => 'High: {{customer_name}}', 'priority' => 'high']]],
            'is_active' => true,
        ]);

        $result = $this->service->process('new_lead', [
            'lead_id' => 1, 'customer_name' => 'VIP', 'lead_score' => 85,
        ], $this->organization->id);

        $this->assertCount(1, $result);
        $this->assertEquals('completed', $result[0]['status']);
        $this->assertEquals(1, Task::count());
    }

    #[Test] public function automation_skips_when_conditions_fail()
    {
        Automation::create([
            'organization_id' => $this->organization->id,
            'name' => 'Test',
            'trigger_type' => 'new_lead',
            'conditions' => [['field' => 'lead_score', 'operator' => 'greater_than', 'value' => 50]],
            'actions' => [['type' => 'create_task', 'config' => ['title' => 'x']]],
            'is_active' => true,
        ]);

        $result = $this->service->process('new_lead', [
            'lead_id' => 1, 'customer_name' => 'Low', 'lead_score' => 10,
        ], $this->organization->id);

        $this->assertEquals('conditions_failed', $result[0]['status']);
        $this->assertEquals(0, Task::count());
    }

    #[Test] public function automation_respects_rate_limits()
    {
        $automation = Automation::create([
            'organization_id' => $this->organization->id,
            'name' => 'Limited',
            'trigger_type' => 'new_lead',
            'is_active' => true,
            'max_executions_per_day' => 2,
            'execution_count_today' => 2,
            'actions' => [['type' => 'create_task', 'config' => ['title' => 'x']]],
        ]);

        $result = $this->service->execute($automation, ['customer_name' => 'Test']);
        $this->assertEquals('rate_limited', $result['status']);
    }

    protected function invokeMethod(object $object, string $method, array $args = [])
    {
        $reflection = new \ReflectionClass($object);
        return $reflection->getMethod($method)->invokeArgs($object, $args);
    }
}