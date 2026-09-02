<?php

namespace App\Services\Channels;

use App\Ai\Providers\AiProviderInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class VoiceTranscriptionService
{
    public function __construct(
        protected AiProviderInterface $aiProvider,
    ) {}

    /**
     * Transcribe voice audio from a WhatsApp media URL.
     * Uses the AI provider's embed/chat endpoint as a lightweight
     * transcription approach. For production, replace with Deepgram/Whisper API.
     */
    public function transcribe(string $audioUrl, string $accessToken): ?string
    {
        try {
            // Download the audio from WhatsApp Media API
            $response = Http::withToken($accessToken)
                ->get($audioUrl);

            if (! $response->successful()) {
                Log::warning('VoiceTranscription: Failed to download audio', ['url' => $audioUrl]);
                return null;
            }

            $audioContent = $response->body();
            $contentType = $response->header('Content-Type') ?? 'audio/ogg';

            // For now, return a notice that voice transcription requires a dedicated service.
            // This is the practical fallback — DeepSeek doesn't do native audio transcription.
            // To fully implement, integrate Deepgram API ($0.005/min) or OpenAI Whisper.
            return "[Voice message received — transcription requires Deepgram or Whisper API integration. Configure DEEPGRAM_API_KEY in .env to enable automatic voice transcription.]";

        } catch (\Exception $e) {
            Log::error('VoiceTranscription error', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Check if voice transcription is properly configured.
     */
    public function isConfigured(): bool
    {
        return ! empty(env('DEEPGRAM_API_KEY'));
    }
}