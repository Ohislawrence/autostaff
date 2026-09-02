<?php

namespace Tests\Feature;

use App\Ai\Providers\AiResponse;
use App\Ai\Understanding\IntentService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class IntentServiceTest extends TestCase
{
    protected function makeService(string $llmJson = '{}'): IntentService
    {
        $provider = $this->createStub(\App\Ai\Providers\AiProviderInterface::class);
        $provider->method('chat')->willReturn(new AiResponse(content: $llmJson));

        return new IntentService($provider);
    }

    #[Test] public function it_extracts_sales_intent_from_llm_json()
    {
        $service = $this->makeService(json_encode([
            'intents' => ['product_inquiry', 'pricing'],
            'entities' => ['product' => 'cement', 'quantity' => 20, 'location' => 'Lekki'],
            'sentiment' => 'neutral',
            'urgency' => 'normal',
            'customer_intent' => 'potential_purchase',
            'confidence' => 0.95,
        ]));

        $result = $service->analyze('How much is cement and can you deliver to Lekki?');

        $this->assertContains('product_inquiry', $result['intents']);
        $this->assertSame('potential_purchase', $result['customer_intent']);
        $this->assertSame(20, $result['entities']['quantity']);
        $this->assertGreaterThan(0.9, $result['confidence']);
    }

    #[Test] public function it_tolerates_markdown_fenced_json()
    {
        $service = $this->makeService("```json\n{\"intents\":[\"greeting\"],\"entities\":{},\"sentiment\":\"positive\",\"urgency\":\"low\",\"customer_intent\":\"information_request\",\"confidence\":0.8}\n```");

        $result = $service->analyze('Hello');

        $this->assertSame(['greeting'], $result['intents']);
        $this->assertSame('positive', $result['sentiment']);
    }

    #[Test] public function it_falls_back_to_keyword_rules_when_llm_returns_garbage()
    {
        $service = $this->makeService('not json at all');

        $result = $service->analyze('How much is 20 bags of rice?');

        $this->assertContains('pricing', $result['intents']);
        $this->assertSame(20, $result['entities']['quantity']);
        $this->assertSame('potential_purchase', $result['customer_intent']);
    }
}