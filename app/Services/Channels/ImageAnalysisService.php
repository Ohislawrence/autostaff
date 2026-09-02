<?php

namespace App\Services\Channels;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ImageAnalysisService
{
    /**
     * Analyze an image and generate a text description.
     * Downloads from WhatsApp Media API and uses AI provider for description.
     *
     * For production use with vision-capable models (GPT-4o, Claude 3),
     * or fall back to basic metadata extraction.
     */
    public function analyze(string $imageUrl, string $accessToken): ?string
    {
        try {
            // Download the image from WhatsApp Media API
            $response = Http::withToken($accessToken)
                ->get($imageUrl);

            if (! $response->successful()) {
                Log::warning('ImageAnalysis: Failed to download image', ['url' => $imageUrl]);
                return null;
            }

            $imageContent = $response->body();
            $contentType = $response->header('Content-Type') ?? 'image/jpeg';
            $fileSize = strlen($imageContent);

            // Build a base64 data URI for vision models
            $base64 = base64_encode($imageContent);
            $dataUri = "data:{$contentType};base64,{$base64}";

            // Check if we have a vision-capable model configured
            if ($this->isVisionConfigured()) {
                return $this->analyzeWithVision($dataUri, $contentType);
            }

            // Fallback: return file metadata
            return "[Image received: {$contentType}, {$fileSize} bytes. Configure OPENAI_API_KEY or ANTHROPIC_API_KEY for AI-powered image understanding (product identification, document analysis, payment proof verification).]";

        } catch (\Exception $e) {
            Log::error('ImageAnalysis error', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Use a vision-capable AI model to describe the image.
     */
    protected function analyzeWithVision(string $dataUri, string $mimeType): ?string
    {
        try {
            $openAiKey = env('OPENAI_API_KEY');
            if ($openAiKey) {
                $response = Http::withToken($openAiKey)
                    ->withOptions(['timeout' => 30])
                    ->post('https://api.openai.com/v1/chat/completions', [
                        'model' => 'gpt-4o',
                        'messages' => [
                            [
                                'role' => 'user',
                                'content' => [
                                    ['type' => 'text', 'text' => 'Describe this image in detail. Focus on: what kind of image it is (product photo, document, payment proof, ID card, etc.), what text is visible, what objects are shown. Respond in a concise paragraph.'],
                                    ['type' => 'image_url', 'image_url' => ['url' => $dataUri]],
                                ],
                            ],
                        ],
                        'max_tokens' => 300,
                    ]);

                if ($response->successful()) {
                    return $response['choices'][0]['message']['content'] ?? null;
                }
            }

            return null;
        } catch (\Exception $e) {
            Log::error('ImageAnalysis vision call failed', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Check if a vision-capable AI model is configured.
     */
    public function isVisionConfigured(): bool
    {
        return ! empty(env('OPENAI_API_KEY')) || ! empty(env('ANTHROPIC_API_KEY'));
    }
}