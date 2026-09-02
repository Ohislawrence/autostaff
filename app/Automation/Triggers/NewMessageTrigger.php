<?php

namespace App\Automation\Triggers;

use App\Automation\Contracts\TriggerInterface;
use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Message;

class NewMessageTrigger implements TriggerInterface
{
    public function identifier(): string
    {
        return 'new_message';
    }

    public function label(): string
    {
        return 'New Message Received';
    }

    public function description(): string
    {
        return 'Fires when a new incoming message is received from a customer via any channel.';
    }

    public function configSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'channels' => [
                    'type' => 'array',
                    'description' => 'Only fire for specific channels (empty = all channels)',
                    'items' => ['type' => 'string', 'enum' => ['web_chat', 'whatsapp', 'email', 'api']],
                ],
                'keyword_filter' => [
                    'type' => 'array',
                    'description' => 'Only fire if message contains any of these keywords (case-insensitive)',
                    'items' => ['type' => 'string'],
                ],
            ],
        ];
    }

    public function extractPayload(object|array $event): array
    {
        /** @var Message $message */
        $message = $event['message'];
        /** @var Conversation $conversation */
        $conversation = $event['conversation'];
        /** @var Customer $customer */
        $customer = $event['customer'];

        return [
            'message_id' => $message->id,
            'conversation_id' => $conversation->id,
            'customer_id' => $customer->id,
            'customer_name' => $customer->first_name . ' ' . $customer->last_name,
            'customer_email' => $customer->email,
            'customer_phone' => $customer->phone,
            'message_content' => $message->content,
            'channel' => $conversation->channel,
            'message_type' => $message->type,
        ];
    }

    public function extractOrganizationId(object|array $event): int
    {
        return $event['message']->organization_id;
    }
}