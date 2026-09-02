<?php

namespace App\Channels\Adapters;

use App\Channels\Contracts\ChannelInterface;
use App\Services\Channels\ImageAnalysisService;
use App\Services\Channels\LanguageDetectionService;
use App\Services\Channels\VoiceTranscriptionService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppAdapter implements ChannelInterface
{
    public function getIdentifier(): string
    {
        return 'whatsapp';
    }

    public function getName(): string
    {
        return 'WhatsApp';
    }

    public function processIncoming(array $payload): array
    {
        // Normalize WhatsApp webhook payload to our internal format
        $entry = $payload['entry'][0] ?? [];
        $change = $entry['changes'][0] ?? [];
        $value = $change['value'] ?? [];
        $messages = $value['messages'] ?? [];
        $message = $messages[0] ?? [];
        $metadata = $value['metadata'] ?? [];

        $customerPhone = $message['from'] ?? '';
        $messageText = '';
        $accessToken = config('services.whatsapp.access_token') ?? '';

        // Handle different message types
        if (isset($message['text']['body'])) {
            $messageText = $message['text']['body'];
        } elseif (isset($message['type'])) {
            $type = $message['type'];
            switch ($type) {
                case 'image':
                    $messageText = $this->handleImageMessage($message, $accessToken);
                    break;
                case 'audio':
                    $messageText = $this->handleAudioMessage($message, $accessToken);
                    break;
                case 'document':
                    $messageText = '[Document received]';
                    break;
                case 'video':
                    $messageText = '[Video received]';
                    break;
                case 'location':
                    $messageText = '[Location shared]';
                    break;
                default:
                    $messageText = "[{$type} message received]";
            }
        }

        return [
            'customer_name' => $value['contacts'][0]['profile']['name'] ?? 'WhatsApp User',
            'customer_phone' => $customerPhone,
            'message' => $messageText,
            'channel' => 'whatsapp',
            'channel_conversation_id' => $customerPhone,
            'external_id' => $customerPhone,
            'phone_number_id' => $metadata['phone_number_id'] ?? null,
            'metadata' => [
                'message_id' => $message['id'] ?? null,
                'message_type' => $message['type'] ?? 'text',
                'timestamp' => $message['timestamp'] ?? null,
                'wa_id' => $value['contacts'][0]['wa_id'] ?? null,
                'phone_number_id' => $metadata['phone_number_id'] ?? null,
            ],
        ];
    }

    /**
     * Handle voice note / audio message — transcribe if possible.
     */
    protected function handleAudioMessage(array $message, string $accessToken): string
    {
        try {
            $mediaId = $message['audio']['id'] ?? ($message['voice']['id'] ?? null);
            if (! $mediaId) return '[Audio message received]';

            $mediaResponse = Http::withToken($accessToken)->get("https://graph.facebook.com/v18.0/{$mediaId}");
            $mediaUrl = $mediaResponse['url'] ?? null;

            if ($mediaUrl) {
                $transcriptionService = app(VoiceTranscriptionService::class);
                $transcription = $transcriptionService->transcribe($mediaUrl, $accessToken);
                if ($transcription && ! str_starts_with($transcription, '[')) {
                    return $transcription;
                }
            }

            return '[Audio message received — enable transcription by configuring DEEPGRAM_API_KEY]';
        } catch (\Exception $e) {
            Log::warning('Audio message handling failed', ['error' => $e->getMessage()]);
            return '[Audio message received]';
        }
    }

    /**
     * Handle image message — analyze if vision services are configured.
     */
    protected function handleImageMessage(array $message, string $accessToken): string
    {
        try {
            $mediaId = $message['image']['id'] ?? null;
            if (! $mediaId) return '[Image received]';

            $mediaResponse = Http::withToken($accessToken)->get("https://graph.facebook.com/v18.0/{$mediaId}");
            $mediaUrl = $mediaResponse['url'] ?? null;

            if ($mediaUrl) {
                $imageService = app(ImageAnalysisService::class);
                $description = $imageService->analyze($mediaUrl, $accessToken);
                if ($description && ! str_starts_with($description, '[')) {
                    return "[Image analyzed: {$description}]";
                }
            }

            return '[Image received — enable AI vision by configuring OPENAI_API_KEY or ANTHROPIC_API_KEY]';
        } catch (\Exception $e) {
            Log::warning('Image message handling failed', ['error' => $e->getMessage()]);
            return '[Image received]';
        }
    }

    public function sendMessage(string $recipient, string $content, array $metadata = []): array
    {
        $phoneNumberId = config('services.whatsapp.phone_number_id');
        $accessToken = config('services.whatsapp.access_token');

        if (! $phoneNumberId || ! $accessToken) {
            Log::warning('WhatsApp not configured', ['recipient' => $recipient]);
            return ['success' => false, 'error' => 'WhatsApp is not configured.'];
        }

        try {
            $response = Http::withToken($accessToken)
                ->post("https://graph.facebook.com/v18.0/{$phoneNumberId}/messages", [
                    'messaging_product' => 'whatsapp',
                    'to' => $recipient,
                    'type' => 'text',
                    'text' => ['body' => $content],
                ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'channel' => 'whatsapp',
                    'message_id' => $response->json('messages.0.id') ?? null,
                ];
            }

            Log::error('WhatsApp send failed', ['response' => $response->body()]);
            return ['success' => false, 'error' => 'Failed to send WhatsApp message.'];

        } catch (\Exception $e) {
            Log::error('WhatsApp send error', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function verifyWebhook(array $payload, array $headers = []): bool
    {
        $verifyToken = config('services.whatsapp.verify_token');
        $mode = $payload['hub_mode'] ?? '';
        $token = $payload['hub_verify_token'] ?? '';
        return $mode === 'subscribe' && $token === $verifyToken;
    }

    public function getConfigSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'access_token' => ['type' => 'string', 'description' => 'WhatsApp Cloud API access token'],
                'phone_number_id' => ['type' => 'string', 'description' => 'WhatsApp phone number ID'],
                'verify_token' => ['type' => 'string', 'description' => 'Webhook verification token'],
            ],
            'required' => ['access_token', 'phone_number_id'],
        ];
    }
}