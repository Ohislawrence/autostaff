<?php

namespace App\Automation\Actions;

use App\Automation\Contracts\ActionInterface;
use App\Models\Conversation;
use Illuminate\Support\Facades\Log;

class SendMessageAction implements ActionInterface
{
    public function identifier(): string
    {
        return 'send_message';
    }

    public function label(): string
    {
        return 'Send Message';
    }

    public function description(): string
    {
        return 'Send a templated message to a conversation.';
    }

    public function configSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'message' => [
                    'type' => 'string',
                    'description' => 'The message content. Use {{variable}} for template variables.',
                ],
                'conversation_id' => [
                    'type' => 'string',
                    'description' => 'Override the conversation ID (defaults to trigger conversation).',
                ],
            ],
            'required' => ['message'],
        ];
    }

    public function execute(array $config, array $triggerData): array
    {
        $conversationId = $triggerData['conversation_id'] ?? $config['conversation_id'] ?? null;
        $message = $this->renderTemplate($config['message'] ?? '', $triggerData);

        if (! $conversationId) {
            return ['success' => false, 'error' => 'No conversation to send message to'];
        }

        $conversation = Conversation::find($conversationId);
        if (! $conversation) {
            return ['success' => false, 'error' => 'Conversation not found'];
        }

        $conversation->messages()->create([
            'organization_id' => $conversation->organization_id,
            'conversation_id' => $conversation->id,
            'type' => 'outgoing',
            'content' => $message,
            'channel' => $conversation->channel,
        ]);

        Log::info('Automation: sent message', [
            'conversation_id' => $conversationId,
            'message_length' => strlen($message),
        ]);

        return ['success' => true, 'action' => 'Message sent'];
    }

    protected function renderTemplate(string $template, array $data): string
    {
        foreach ($data as $key => $value) {
            if (is_scalar($value)) {
                $template = str_replace('{{' . $key . '}}', (string) $value, $template);
            }
        }
        return $template;
    }
}