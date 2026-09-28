<?php

namespace App\Http\Controllers;

use App\Ai\Orchestrator\AiOrchestrator;
use App\Models\AiEmployee;
use App\Services\Automation\AutomationService;
use App\Services\ConversationService;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function __construct(
        protected ConversationService $conversationService,
        protected AiOrchestrator $orchestrator,
        protected AutomationService $automationService,
    ) {}

    /**
     * Process an incoming message from any channel (web chat, WhatsApp, API).
     * This is the public endpoint for the chat widget.
     */
    public function sendMessage(Request $request, AiEmployee $aiEmployee)
    {
        $request->validate([
            'message' => 'required|string|max:5000',
            'customer_name' => 'nullable|string|max:255',
            'customer_email' => 'nullable|email|max:255',
            'customer_phone' => 'nullable|string|max:50',
            'channel' => 'nullable|string|max:50',
            'channel_conversation_id' => 'nullable|string|max:255',
        ]);

        $organization = $aiEmployee->organization;

        if (! $aiEmployee->is_active) {
            return response()->json([
                'success' => false,
                'error' => 'This AI employee is currently inactive.',
            ], 400);
        }

        // Set tenant context
        app()->instance('current_organization_id', $organization->id);

        // Find or create customer
        $customer = $this->conversationService->findOrCreateCustomer($organization, [
            'first_name' => $request->customer_name ?? 'Guest',
            'email' => $request->customer_email,
            'phone' => $request->customer_phone,
            'channel' => $request->channel ?? 'web_chat',
            'external_id' => $request->channel_conversation_id,
        ]);

        // Find or create conversation
        $conversation = $this->conversationService->findOrCreateConversation(
            $organization,
            $aiEmployee,
            $customer,
            $request->channel ?? 'web_chat',
            $request->channel_conversation_id
        );

        // Human handoff: once a human is handling this conversation, route the
        // visitor's message to the team instead of running the AI again.
        if (in_array($conversation->status, ['human_required', 'assigned', 'waiting_customer'], true)) {
            $this->conversationService->createIncomingMessage($conversation, $customer, $request->message, [
                'channel' => $request->channel ?? 'web_chat',
                'source' => 'chat_widget',
            ]);

            return response()->json([
                'success' => true,
                'response' => 'Your message has been sent to our team — a human agent will get back to you shortly.',
                'conversation_id' => $conversation->uuid,
                'human_handled' => true,
            ]);
        }

        // Create the incoming message record first (this is the event that triggers automations)
        $incomingMessage = $this->conversationService->createIncomingMessage(
            $conversation,
            $customer,
            $request->message,
            [
                'channel' => $request->channel ?? 'web_chat',
                'source' => 'chat_widget',
            ]
        );

        // Fire automation trigger for new message received
        try {
            $this->automationService->triggerOnMessageReceived(
                $incomingMessage,
                $conversation,
                $customer
            );
        } catch (\Exception $e) {
            // Don't block message processing for automation failures
            \Illuminate\Support\Facades\Log::warning('Automation trigger failed in ChatController', [
                'error' => $e->getMessage(),
            ]);
        }

        // Process through AI Orchestrator (pass the pre-created message to avoid duplicate)
        $result = $this->orchestrator->processIncomingMessage(
            $aiEmployee,
            $conversation,
            $customer,
            $request->message,
            [
                'channel' => $request->channel ?? 'web_chat',
                'source' => 'chat_widget',
            ],
            $incomingMessage,
        );

        return response()->json([
            'success' => $result['success'] ?? false,
            'response' => $result['response'] ?? 'I apologize, but I was unable to process your message.',
            'conversation_id' => $conversation->uuid,
            'tool_calls' => $result['tool_calls'] ?? [],
            'escalated' => $result['escalated'] ?? false,
        ]);
    }

    /**
     * Poll for new human messages on a conversation (used by the chat widget so
     * the visitor sees human replies in real time after a handoff).
     */
    public function pollConversation(Request $request, AiEmployee $aiEmployee, string $conversationUuid)
    {
        $organization = $aiEmployee->organization;

        $conversation = \App\Models\Conversation::where('organization_id', $organization->id)
            ->where('uuid', $conversationUuid)
            ->first();

        if (! $conversation) {
            return response()->json(['success' => false, 'error' => 'Conversation not found.'], 404);
        }

        $afterId = (int) $request->query('after_id', 0);

        $messages = $conversation->messages()
            ->where('type', 'human_response')
            ->when($afterId > 0, fn ($q) => $q->where('id', '>', $afterId))
            ->orderBy('id')
            ->get(['id', 'type', 'content', 'created_at'])
            ->map(fn ($m) => [
                'id' => $m->id,
                'type' => $m->type,
                'content' => $m->content,
                'created_at' => $m->created_at?->toISOString(),
            ])
            ->values();

        return response()->json([
            'success' => true,
            'status' => $conversation->status,
            'messages' => $messages,
            'last_id' => $messages->last()['id'] ?? $afterId,
        ]);
    }
}