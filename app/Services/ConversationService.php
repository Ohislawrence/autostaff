<?php

namespace App\Services;

use App\Models\AiEmployee;
use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Message;
use App\Models\Organization;

class ConversationService
{
    /**
     * Find or create a customer from incoming message data.
     */
    public function findOrCreateCustomer(Organization $organization, array $customerData): Customer
    {
        $identifier = $customerData['external_id'] ?? null;
        $channel = $customerData['channel'] ?? 'web_chat';

        // Try to find by external ID + channel
        if ($identifier) {
            $customer = Customer::where('organization_id', $organization->id)
                ->where('external_id', $identifier)
                ->where('channel', $channel)
                ->first();

            if ($customer) {
                // Update last contacted
                $customer->update(['last_contacted_at' => now()]);
                return $customer;
            }
        }

        // Try to find by email or phone
        if (! empty($customerData['email'])) {
            $customer = Customer::where('organization_id', $organization->id)
                ->where('email', $customerData['email'])
                ->first();
            if ($customer) return $customer;
        }

        if (! empty($customerData['phone'])) {
            $customer = Customer::where('organization_id', $organization->id)
                ->where('phone', $customerData['phone'])
                ->first();
            if ($customer) return $customer;
        }

        // Create new customer
        return Customer::create([
            'organization_id' => $organization->id,
            'first_name' => $customerData['first_name'] ?? 'Guest',
            'last_name' => $customerData['last_name'] ?? null,
            'email' => $customerData['email'] ?? null,
            'phone' => $customerData['phone'] ?? null,
            'source' => $channel,
            'channel' => $channel,
            'external_id' => $identifier,
        ]);
    }

    /**
     * Find or create a conversation for a customer on a channel.
     */
    public function findOrCreateConversation(
        Organization $organization,
        AiEmployee $employee,
        Customer $customer,
        string $channel,
        ?string $channelConversationId = null
    ): Conversation {
        // Look for an active conversation on this channel for this customer.
        // Human-owned conversations (human_required / assigned / waiting_customer)
        // are included so a live handoff keeps routing to the same thread.
        $conversation = Conversation::where('organization_id', $organization->id)
            ->where('customer_id', $customer->id)
            ->where('channel', $channel)
            ->whereIn('status', ['open', 'ai_handling', 'waiting_customer', 'human_required', 'assigned'])
            ->latest()
            ->first();

        if ($conversation) {
            $humanOwned = in_array($conversation->status, ['human_required', 'assigned', 'waiting_customer'], true);

            $conversation->update([
                'ai_employee_id' => $employee->id,
                // Preserve human-handoff state; otherwise resume AI handling.
                'status' => $humanOwned ? $conversation->status : 'ai_handling',
            ]);

            return $conversation;
        }

        // Create new conversation
        return Conversation::create([
            'organization_id' => $organization->id,
            'ai_employee_id' => $employee->id,
            'customer_id' => $customer->id,
            'channel' => $channel,
            'channel_conversation_id' => $channelConversationId,
            'status' => 'ai_handling',
            'last_message_at' => now(),
        ]);
    }

    /**
     * Create an incoming message.
     */
    public function createIncomingMessage(
        Conversation $conversation,
        Customer $customer,
        string $content,
        array $metadata = []
    ): Message {
        $conversation->update(['last_message_at' => now()]);

        return Message::create([
            'organization_id' => $conversation->organization_id,
            'conversation_id' => $conversation->id,
            'customer_id' => $customer->id,
            'type' => 'incoming',
            'content' => $content,
            'channel' => $conversation->channel,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Create an AI response message.
     */
    public function createAiResponse(
        Conversation $conversation,
        AiEmployee $employee,
        string $content,
        array $metadata = []
    ): Message {
        $conversation->update([
            'last_message_at' => now(),
            'status' => 'ai_handling',
        ]);

        // Increment conversation count
        $employee->increment('conversations_count');

        return Message::create([
            'organization_id' => $conversation->organization_id,
            'conversation_id' => $conversation->id,
            'ai_employee_id' => $employee->id,
            'type' => 'ai_response',
            'content' => $content,
            'channel' => $conversation->channel,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Create a system event message.
     */
    public function createSystemEvent(
        Conversation $conversation,
        string $event,
        array $metadata = []
    ): Message {
        return Message::create([
            'organization_id' => $conversation->organization_id,
            'conversation_id' => $conversation->id,
            'type' => 'system_event',
            'content' => $event,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Escalate a conversation to a human, producing a structured handoff.
     */
    public function escalateToHuman(Conversation $conversation, string $reason, ?string $summary = null): void
    {
        // Build the full structured handoff payload (customer, orders, actions, summary).
        try {
            $handoff = app(\App\Services\HandoffService::class)
                ->build($conversation, $reason, $summary);
        } catch (\Throwable $e) {
            $handoff = null;
        }

        $conversation->update([
            'status' => 'human_required',
            'priority' => 'high',
            'ai_summary' => $summary ?? ($handoff['summary'] ?? null),
        ]);

        $this->createSystemEvent($conversation, "Conversation escalated to human: {$reason}", [
            'escalation_reason' => $reason,
            'ai_summary' => $summary,
            'handoff' => $handoff,
        ]);
    }

    /**
     * Check if conversation should be escalated based on employee rules.
     */
    public function shouldEscalate(Conversation $conversation, AiEmployee $employee, string $customerMessage): bool
    {
        // Check for escalation keywords
        $escalationRules = $employee->escalation_rules;
        if ($escalationRules && isset($escalationRules['keywords'])) {
            $msg = strtolower($customerMessage);
            foreach ($escalationRules['keywords'] as $keyword) {
                if (str_contains($msg, strtolower($keyword))) {
                    return true;
                }
            }
        }

        // Check for explicit human request
        $humanRequests = ['talk to human', 'speak to human', 'talk to a human', 'speak to a human', 'real person', 'human agent', 'not a bot'];
        $msg = strtolower($customerMessage);
        foreach ($humanRequests as $phrase) {
            if (str_contains($msg, $phrase)) {
                return true;
            }
        }

        // Check repetition — customer repeating themselves signals frustration
        $recentIncoming = $conversation->messages()
            ->where('type', 'incoming')
            ->latest()
            ->take(3)
            ->pluck('content')
            ->toArray();

        if (count($recentIncoming) >= 2) {
            $lastMsg = strtolower($recentIncoming[0] ?? '');
            $prevMsg = strtolower($recentIncoming[1] ?? '');
            similar_text($lastMsg, $prevMsg, $similarity);
            if ($similarity > 70) {
                return true; // Customer is repeating themselves — escalating
            }
        }

        // Check turn threshold — 8+ incoming messages without resolution
        $messageCount = $conversation->messages()->where('type', 'incoming')->count();
        if ($messageCount > 8) {
            return true;
        }

        return false;
    }

    /**
     * Resolve a conversation.
     */
    public function resolve(Conversation $conversation): void
    {
        $conversation->update([
            'status' => 'resolved',
            'resolved_at' => now(),
        ]);

        // Persist conversation summary to customer for memory across sessions
        $this->saveCustomerMemory($conversation);
    }

    /**
     * Close a conversation.
     */
    public function close(Conversation $conversation): void
    {
        $conversation->update([
            'status' => 'closed',
            'closed_at' => now(),
        ]);

        // Persist conversation summary to customer for memory across sessions
        $this->saveCustomerMemory($conversation);
    }

    /**
     * Save a summary of this conversation to the customer's ai_summary field
     * so future conversations have context about returning customers.
     */
    protected function saveCustomerMemory(Conversation $conversation): void
    {
        try {
            $customer = $conversation->customer;
            if (! $customer) return;

            // Build summary from conversation data
            $messageCount = $conversation->messages()->count();
            $aiMessages = $conversation->messages()->where('type', 'ai_response')->count();
            $summary = [
                'last_conversation_at' => now()->toISOString(),
                'channel' => $conversation->channel,
                'status' => $conversation->status,
                'total_messages' => $messageCount,
                'ai_responses' => $aiMessages,
                'topics' => $this->extractTopics($conversation),
                'escalated' => $conversation->status === 'human_required',
            ];

            // Merge with existing summaries
            $existing = $customer->ai_summary ?? [];
            $history = $existing['history'] ?? [];
            $history[] = $summary;

            // Keep only last 10 summaries
            if (count($history) > 10) {
                $history = array_slice($history, -10);
            }

            $customer->update([
                'ai_summary' => array_merge($existing, [
                    'history' => $history,
                    'last_interaction' => now()->toISOString(),
                    'total_conversations' => count($history),
                ]),
                'last_contacted_at' => now(),
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('Failed to save customer memory', [
                'conversation_id' => $conversation->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Extract key topics from conversation messages.
     */
    protected function extractTopics(Conversation $conversation): array
    {
        try {
            // Simple keyword extraction from incoming messages
            $incomingMessages = $conversation->messages()
                ->where('type', 'incoming')
                ->pluck('content')
                ->toArray();

            $text = implode(' ', $incomingMessages);
            $text = strtolower($text);

            $topics = [];
            $keywords = [
                'price' => 'pricing',
                'cost' => 'pricing',
                'order' => 'order',
                'delivery' => 'delivery',
                'shipping' => 'shipping',
                'appointment' => 'appointment',
                'booking' => 'appointment',
                'product' => 'product_info',
                'refund' => 'refund',
                'return' => 'refund',
                'complaint' => 'complaint',
                'help' => 'support',
                'payment' => 'payment',
                'invoice' => 'payment',
                'quotation' => 'sales',
                'quote' => 'sales',
                'buy' => 'sales',
            ];

            foreach ($keywords as $word => $topic) {
                if (str_contains($text, $word) && ! in_array($topic, $topics)) {
                    $topics[] = $topic;
                }
            }

            return $topics;
        } catch (\Exception $e) {
            return [];
        }
    }
}
