<?php

namespace App\Console\Commands;

use App\Ai\Providers\DeepSeekProvider;
use Illuminate\Console\Command;

class TestDeepSeekCommand extends Command
{
    protected $signature = 'test:deepseek';
    protected $description = 'Test DeepSeek AI provider connectivity and capabilities';

    public function handle(): int
    {
        $this->info('═══ DeepSeek Provider Test ═══');

        // 1. Check configuration
        $apiKey = env('DEEPSEEK_API_KEY');
        $baseUrl = env('DEEPSEEK_BASE_URL', 'https://api.deepseek.com');
        $model = env('DEEPSEEK_MODEL', 'deepseek-chat');

        $this->line('');
        $this->line("Base URL:   <fg=cyan>{$baseUrl}</>");
        $this->line("Model:      <fg=cyan>{$model}</>");
        $this->line("API Key:    <fg=cyan>" . substr($apiKey, 0, 8) . '...' . substr($apiKey, -4) . "</>");
        $this->line('');

        if (empty($apiKey)) {
            $this->error('❌ DEEPSEEK_API_KEY is not set in .env');
            return self::FAILURE;
        }

        // 2. Initialize provider
        try {
            $provider = new DeepSeekProvider();
            $this->info('✅ Provider instantiated');
        } catch (\Exception $e) {
            $this->error('❌ Failed to create provider: ' . $e->getMessage());
            return self::FAILURE;
        }

        // 3. Health check
        try {
            $available = $provider->isAvailable();
            if ($available) {
                $this->info('✅ Provider health check passed (isAvailable = true)');
            } else {
                $this->warn('⚠ Provider health check returned false');
            }
        } catch (\Exception $e) {
            $this->error('❌ Health check threw exception: ' . $e->getMessage());
            $this->line('');
            $this->line('Attempting a real chat call to diagnose...');
        }

        // 4. Simple chat test
        $this->line('');
        $this->line('--- Chat Completion Test ---');
        $startMs = microtime(true);

        try {
            $response = $provider->chat([
                ['role' => 'system', 'content' => 'You are a helpful assistant. Keep responses brief.'],
                ['role' => 'user', 'content' => 'Say "Hello from nomdal!" in exactly those words, nothing else.'],
            ], [
                'max_tokens' => 50,
                'temperature' => 0.1,
            ]);

            $elapsed = round((microtime(true) - $startMs) * 1000);

            if (! empty($response->content)) {
                $this->info('✅ Chat call succeeded');
                $this->line("   Response:  <fg=green>{$response->content}</>");
                $this->line("   Model:     {$response->model}");
                $this->line("   Tokens:    in={$response->inputTokens} out={$response->outputTokens}");
                $this->line("   Latency:   {$elapsed}ms");
                $this->line("   Finish:    {$response->finishReason}");
            } else {
                $this->error('❌ Chat returned empty content');
                $this->line("   Metadata: " . json_encode($response->metadata));
                return self::FAILURE;
            }
        } catch (\Exception $e) {
            $this->error('❌ Chat call failed: ' . $e->getMessage());
            $this->line('');
            $this->line('Possible causes:');
            $this->line('  1. Model name "' . $model . '" may be invalid. Try: deepseek-chat or deepseek-reasoner');
            $this->line('  2. API key may be invalid or expired');
            $this->line('  3. Network/firewall issue reaching ' . $baseUrl);
            $this->line('  4. Account may have insufficient credits');
            return self::FAILURE;
        }

        // 5. Embedding test (if model supports it)
        $this->line('');
        $this->line('--- Embedding Test ---');
        $startMs = microtime(true);

        try {
            $embeddings = $provider->embed('Hello world test');
            $elapsed = round((microtime(true) - $startMs) * 1000);

            if (! empty($embeddings)) {
                $this->info('✅ Embedding call succeeded');
                $this->line("   Dimensions: " . count($embeddings[0] ?? []));
                $this->line("   Latency:    {$elapsed}ms");
            } else {
                $this->warn('⚠ Embedding returned empty array (model may not support embeddings)');
            }
        } catch (\Exception $e) {
            $this->warn('⚠ Embedding test failed (non-fatal): ' . $e->getMessage());
            $this->line('   This is expected for some models that do not support /v1/embeddings');
        }

        $this->line('');
        $this->info('═══ Test Complete — DeepSeek is operational ═══');
        return self::SUCCESS;
    }
}