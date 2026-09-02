<?php

namespace App\Ai\Understanding;

use App\Ai\Providers\AiProviderInterface;
use Illuminate\Support\Facades\Log;

/**
 * Structured "UNDERSTAND" stage of the AI Employee pipeline.
 *
 * Produces a machine-readable interpretation of an incoming message:
 *   - intents[]          what the customer is trying to do
 *   - entities[]         extracted values (product, quantity, location, order, amount…)
 *   - sentiment          positive / neutral / negative
 *   - urgency            low / normal / high
 *   - customer_intent    broad classification
 *   - confidence         0..1
 *
 * The LLM is the primary engine, but a deterministic keyword fallback guarantees
 * the pipeline never breaks if the provider is unavailable or returns bad JSON.
 */
class IntentService
{
    public function __construct(
        protected AiProviderInterface $aiProvider,
    ) {}

    public function analyze(string $text, array $context = [], bool $useLlm = true): array
    {
        $text = trim($text);
        if ($text === '') {
            return $this->blank();
        }

        if (! $useLlm) {
            return $this->fallback($text);
        }

        try {
            $response = $this->aiProvider->chat(
                [
                    ['role' => 'system', 'content' => $this->systemPrompt()],
                    ['role' => 'user', 'content' => $text],
                ],
                ['temperature' => 0, 'max_tokens' => 350]
            );

            $content = trim($response->content ?? '');
            if ($content === '') {
                return $this->fallback($text);
            }

            $parsed = $this->decodeJson($content);
            if ($parsed === null) {
                return $this->fallback($text);
            }

            return $this->normalize($parsed, $text);
        } catch (\Throwable $e) {
            Log::warning('Intent analysis failed, falling back to keyword rules', [
                'error' => $e->getMessage(),
            ]);

            return $this->fallback($text);
        }
    }

    protected function systemPrompt(): string
    {
        return <<<PROMPT
You are an intent-understanding engine for a business's AI employee.

Analyze the customer message and return ONLY a valid JSON object with this exact shape:
{
  "intents": ["product_inquiry"],
  "entities": {"product": "cement", "quantity": 20, "location": "Lekki", "amount": null, "order_number": null},
  "sentiment": "neutral",
  "urgency": "normal",
  "customer_intent": "potential_purchase",
  "confidence": 0.9
}

Rules:
- "intents" is an array. Use only these values: product_inquiry, pricing, delivery_inquiry, order_status, payment, appointment, support, complaint, refund, sales, greeting, other.
- "entities" is an object. Extract only what is present; use null for missing values.
- "sentiment" is one of: positive, neutral, negative.
- "urgency" is one of: low, normal, high.
- "customer_intent" is one of: potential_purchase, existing_customer_support, complaint, appointment_booking, information_request, payment_verification, other.
- "confidence" is a number 0..1.
Do not include any text outside the JSON object.
PROMPT;
    }

    /**
     * Deterministic fallback so the pipeline never depends on the provider.
     */
    protected function fallback(string $text): array
    {
        $lower = mb_strtolower($text);

        $intents = [];
        $entities = [];

        $keywordIntentMap = [
            'product_inquiry' => ['product', 'item', 'stock', 'available', 'do you have', 'sell'],
            'pricing' => ['price', 'cost', 'how much', '₦', '$', 'rate', 'fee'],
            'delivery_inquiry' => ['deliver', 'shipping', 'lekki', 'lagos', 'abuja', 'dispatch', 'transport'],
            'order_status' => ['order', 'track', 'status', 'where is my'],
            'payment' => ['paid', 'payment', 'invoice', 'pay', 'receipt', 'transfer'],
            'appointment' => ['appointment', 'book', 'schedule', 'reserve', 'slot'],
            'support' => ['help', 'issue', 'problem', 'not working', 'assist'],
            'complaint' => ['complaint', 'angry', 'bad', 'terrible', 'disappointed'],
            'refund' => ['refund', 'return', 'money back', 'cancel'],
            'sales' => ['buy', 'purchase', 'i want', 'i need', 'interested'],
            'greeting' => ['hello', 'hi', 'good morning', 'good afternoon', 'good evening', 'hey'],
        ];

        foreach ($keywordIntentMap as $intent => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($lower, $keyword)) {
                    $intents[] = $intent;
                    break;
                }
            }
        }

        if (empty($intents)) {
            $intents[] = 'other';
        }

        // Light entity extraction
        if (preg_match('/(\d+)\s*(bags?|units?|pcs?|kg|pieces?)/i', $text, $m)) {
            $entities['quantity'] = (int) $m[1];
        }
        if (preg_match('/order\s*#?\s*([A-Za-z0-9\-]+)/i', $text, $m)) {
            $entities['order_number'] = $m[1];
        }
        if (preg_match('/₦?\s?([0-9,]+(?:\.\d+)?)/', $text, $m)) {
            $entities['amount'] = str_replace(',', '', $m[1]);
        }

        $sentiment = 'neutral';
        $positive = ['thank', 'great', 'good', 'awesome', 'perfect', 'happy', 'love'];
        $negative = ['angry', 'bad', 'terrible', 'disappointed', 'slow', 'frustrated', 'refund', 'complaint', 'cancel', 'not working'];

        foreach ($negative as $w) {
            if (str_contains($lower, $w)) {
                $sentiment = 'negative';
                break;
            }
        }
        if ($sentiment === 'neutral') {
            foreach ($positive as $w) {
                if (str_contains($lower, $w)) {
                    $sentiment = 'positive';
                    break;
                }
            }
        }

        $urgency = 'normal';
        $urgent = ['asap', 'urgent', 'immediately', 'emergency', 'right now', 'today'];
        foreach ($urgent as $w) {
            if (str_contains($lower, $w)) {
                $urgency = 'high';
                break;
            }
        }

        return $this->normalize([
            'intents' => array_values(array_unique($intents)),
            'entities' => $entities,
            'sentiment' => $sentiment,
            'urgency' => $urgency,
            'customer_intent' => $this->inferCustomerIntent($intents, $sentiment),
            'confidence' => $this->isFallbackConfidenceHigh($intents, $entities) ? 0.5 : 0.3,
        ], $text);
    }

    protected function inferCustomerIntent(array $intents, string $sentiment): string
    {
        if (array_intersect($intents, ['complaint', 'refund'])) {
            return 'complaint';
        }
        if (in_array('appointment', $intents, true)) {
            return 'appointment_booking';
        }
        if (array_intersect($intents, ['payment'])) {
            return 'payment_verification';
        }
        if (array_intersect($intents, ['sales', 'product_inquiry', 'pricing', 'delivery_inquiry'])) {
            return 'potential_purchase';
        }
        if (in_array('order_status', $intents, true) || in_array('support', $intents, true)) {
            return 'existing_customer_support';
        }

        return 'information_request';
    }

    protected function isFallbackConfidenceHigh(array $intents, array $entities): bool
    {
        return in_array('sales', $intents, true) || ! empty($entities);
    }

    /**
     * Decode JSON from an LLM response, tolerating markdown fences.
     */
    protected function decodeJson(string $content): ?array
    {
        $content = trim($content);

        // Strip ```json ... ``` fences if present
        if (preg_match('/```(?:json)?\s*(\{.*\})\s*```/s', $content, $m)) {
            $content = $m[1];
        }

        $firstBrace = strpos($content, '{');
        $lastBrace = strrpos($content, '}');
        if ($firstBrace !== false && $lastBrace !== false && $lastBrace > $firstBrace) {
            $content = substr($content, $firstBrace, $lastBrace - $firstBrace + 1);
        }

        $decoded = json_decode($content, true);

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * Ensure every expected key exists with a valid default.
     */
    protected function normalize(array $data, string $text): array
    {
        $intents = $data['intents'] ?? [];
        if (! is_array($intents)) {
            $intents = [];
        }
        $intents = array_values(array_filter(array_map('strval', $intents)));
        if (empty($intents)) {
            $intents = ['other'];
        }

        $entities = $data['entities'] ?? [];
        if (! is_array($entities)) {
            $entities = [];
        }

        $sentiment = in_array($data['sentiment'] ?? '', ['positive', 'neutral', 'negative'], true)
            ? $data['sentiment']
            : 'neutral';

        $urgency = in_array($data['urgency'] ?? '', ['low', 'normal', 'high'], true)
            ? $data['urgency']
            : 'normal';

        $confidence = is_numeric($data['confidence'] ?? null)
            ? (float) $data['confidence']
            : 0.4;
        $confidence = max(0, min(1, $confidence));

        return [
            'intents' => $intents,
            'entities' => $entities,
            'sentiment' => $sentiment,
            'urgency' => $urgency,
            'customer_intent' => $data['customer_intent'] ?? 'other',
            'confidence' => round($confidence, 3),
            'text' => $text,
        ];
    }

    protected function blank(): array
    {
        return [
            'intents' => ['other'],
            'entities' => [],
            'sentiment' => 'neutral',
            'urgency' => 'normal',
            'customer_intent' => 'other',
            'confidence' => 0,
            'text' => '',
        ];
    }
}