<?php

namespace App\Http\Controllers\Api\V1;

use App\Channels\ChannelManager;
use App\Http\Controllers\Controller;
use App\Models\AiEmployee;
use App\Models\Integration;
use App\Models\Order;
use App\Models\Organization;
use App\Services\Commerce\ProductSyncService;
use App\Services\ConversationService;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    public function __construct(
        protected ChannelManager $channels,
        protected ConversationService $conversationService,
    ) {}

    public function whatsapp(Request $request)
    {
        // WhatsApp webhook verification (GET request)
        if ($request->isMethod('GET')) {
            $mode = $request->query('hub.mode');
            $token = $request->query('hub.verify_token');
            $challenge = $request->query('hub.challenge');

            $verifyToken = config('services.whatsapp.verify_token');

            if ($mode === 'subscribe' && $token === $verifyToken) {
                return response($challenge, 200)->header('Content-Type', 'text/plain');
            }

            return response()->json(['error' => 'Verification failed'], 403);
        }

        // Handle incoming message (POST request)
        $payload = $request->all();

        // WhatsApp sends status updates too - only process messages
        $entry = $payload['entry'][0] ?? [];
        $changes = $entry['changes'] ?? [];
        
        if (empty($changes)) {
            // Acknowledge but don't process
            return response()->json(['success' => true, 'message' => 'No changes to process']);
        }

        $value = $changes[0]['value'] ?? [];
        $messages = $value['messages'] ?? [];
        
        // If no messages (e.g., status update), acknowledge and return
        if (empty($messages)) {
            return response()->json(['success' => true, 'message' => 'Status update received']);
        }

        // Parse incoming message
        $adapter = $this->channels->get('whatsapp');
        $parsed = $adapter->processIncoming($payload);
        
        // Verify we have a message to process
        if (empty($parsed['message']) || empty($parsed['customer_phone'])) {
            return response()->json(['success' => false, 'error' => 'Invalid message payload'], 400);
        }

        return $this->handleIncoming($parsed, 'whatsapp');
    }

    public function email(Request $request)
    {
        $payload = $request->all();

        // Verify webhook signature
        $adapter = $this->channels->get('email');
        $headers = $request->headers->all();
        // Flatten headers for simpler access
        $flatHeaders = [];
        foreach ($headers as $key => $values) {
            $flatHeaders[strtolower($key)] = is_array($values) ? implode(', ', $values) : $values;
        }

        if (! $adapter->verifyWebhook($payload, $flatHeaders)) {
            return response()->json(['error' => 'Invalid signature'], 403);
        }

        // Parse incoming email
        $parsed = $adapter->processIncoming([
            'from' => $payload['from'] ?? $payload['sender'] ?? '',
            'subject' => $payload['subject'] ?? 'No Subject',
            'body' => $payload['body'] ?? $payload['stripped-text'] ?? $payload['text'] ?? '',
            'to' => $payload['to'] ?? $payload['recipient'] ?? '',
        ]);

        return $this->handleIncoming($parsed, 'email');
    }

    public function woocommerce(Request $request)
    {
        $rawBody = $request->getContent();
        $signature = (string) $request->header('X-WC-Webhook-Signature', '');
        $source = (string) $request->header('X-WC-Webhook-Source', '');
        $topic = (string) $request->header('X-WC-Webhook-Topic', '');

        // Identify the tenant by the store URL the webhook came from.
        $integration = Integration::query()
            ->where('provider', 'woocommerce')
            ->where('is_connected', true)
            ->get()
            ->first(fn ($i) => $this->storeUrlsMatch((string) $i->getConfigValue('store_url', ''), $source));

        if (! $integration) {
            return response()->json(['error' => 'Unknown store'], 404);
        }

        $secret = $integration->getConfigValue('webhook_secret');
        if ($secret && $signature) {
            $expected = base64_encode(hash_hmac('sha256', $rawBody, $secret, true));
            if (! hash_equals($expected, $signature)) {
                return response()->json(['error' => 'Invalid signature'], 403);
            }
        }

        $payload = $request->json()->all();

        if (in_array($topic, ['order.created', 'order.updated'], true)) {
            $externalId = (string) ($payload['id'] ?? '');
            if ($externalId !== '') {
                $order = Order::where('organization_id', $integration->organization_id)
                    ->get()
                    ->first(fn ($o) => ($o->metadata['external_order_id'] ?? null) === $externalId);

                if ($order) {
                    $status = $payload['status'] ?? $order->status;
                    $order->update([
                        'status' => $status,
                        'total' => $payload['total'] ?? $order->total,
                        'currency' => $payload['currency'] ?? $order->currency,
                        'payment_status' => in_array($status, ['processing', 'completed'], true) ? 'paid' : $order->payment_status,
                    ]);
                }
            }
        }

        if (in_array($topic, ['product.created', 'product.updated'], true)) {
            $organization = Organization::find($integration->organization_id);
            if ($organization) {
                app()->instance('current_organization_id', $organization->id);

                app(ProductSyncService::class)->upsertProduct($organization, [
                    'external_id' => (string) ($payload['id'] ?? ''),
                    'name' => $payload['name'] ?? '',
                    'sku' => $payload['sku'] ?? null,
                    'price' => (string) ($payload['price'] ?? '0'),
                    'sale_price' => (isset($payload['sale_price']) && $payload['sale_price'] !== '') ? (string) $payload['sale_price'] : null,
                    'stock_quantity' => isset($payload['stock_quantity']) ? (int) $payload['stock_quantity'] : null,
                    'categories' => array_map(fn ($c) => $c['name'] ?? '', $payload['categories'] ?? []),
                    'is_active' => ($payload['status'] ?? 'publish') === 'publish',
                ]);
            }
        }

        return response()->json(['success' => true]);
    }

    public function shopify(Request $request)
    {
        $rawBody = $request->getContent();
        $signature = (string) $request->header('X-Shopify-Hmac-SHA256', '');
        $shopDomain = (string) $request->header('X-Shopify-Shop-Domain', '');
        $topic = (string) $request->header('X-Shopify-Topic', '');

        $integration = Integration::query()
            ->where('provider', 'shopify')
            ->where('is_connected', true)
            ->get()
            ->first(fn ($i) => $this->storeUrlsMatch((string) $i->getConfigValue('shop_domain', ''), $shopDomain));

        if (! $integration) {
            return response()->json(['error' => 'Unknown store'], 404);
        }

        $secret = $integration->getConfigValue('webhook_secret');
        if ($secret && $signature) {
            $expected = base64_encode(hash_hmac('sha256', $rawBody, (string) $secret, true));
            if (! hash_equals($expected, $signature)) {
                return response()->json(['error' => 'Invalid signature'], 403);
            }
        }

        $payload = $request->json()->all();

        if (str_starts_with($topic, 'orders/')) {
            $externalId = (string) ($payload['id'] ?? '');
            if ($externalId !== '') {
                $order = Order::where('organization_id', $integration->organization_id)
                    ->get()
                    ->first(fn ($o) => ($o->metadata['external_order_id'] ?? null) === $externalId);

                if ($order) {
                    $status = $order->status;
                    $paymentStatus = $order->payment_status;

                    if (! empty($payload['cancelled_at'])) {
                        $status = 'cancelled';
                    } elseif (($payload['financial_status'] ?? '') === 'refunded') {
                        $status = 'refunded';
                    } elseif (($payload['fulfillment_status'] ?? '') === 'fulfilled') {
                        $status = 'delivered';
                    } elseif (($payload['financial_status'] ?? '') === 'paid') {
                        $status = 'confirmed';
                        $paymentStatus = 'paid';
                    }

                    $order->update([
                        'status' => $status,
                        'total' => $payload['total_price'] ?? $order->total,
                        'currency' => $payload['currency'] ?? $order->currency,
                        'payment_status' => $paymentStatus,
                    ]);
                }
            }
        }

        return response()->json(['success' => true]);
    }

    protected function storeUrlsMatch(string $a, string $b): bool
    {
        return hash_equals(strtolower(rtrim($a, '/')), strtolower(rtrim($b, '/')));
    }

    protected function handleIncoming(array $parsed, string $channel): \Illuminate\Http\JsonResponse
    {
        $customerEmail = $parsed['customer_email'] ?? null;
        $customerPhone = $parsed['customer_phone'] ?? null;
        $customerName = $parsed['customer_name'] ?? 'Guest';
        $messageContent = $parsed['message'] ?? '';
        $externalId = $parsed['external_id'] ?? ($customerEmail ?? $customerPhone ?? uniqid('guest_', true));

        if (empty($messageContent)) {
            return response()->json(['success' => false, 'error' => 'No message content'], 400);
        }

        // For WhatsApp, use phone_number_id to find the correct tenant
        $organization = null;
        if ($channel === 'whatsapp' && !empty($parsed['phone_number_id'])) {
            $phoneNumberId = $parsed['phone_number_id'];
            
            // Find Integration with this phone_number_id in config
            $integration = \App\Models\Integration::where('provider', 'whatsapp')
                ->where('is_connected', true)
                ->get()
                ->first(function ($int) use ($phoneNumberId) {
                    $config = $int->config ?? [];
                    return isset($config['phone_number_id']) && $config['phone_number_id'] === $phoneNumberId;
                });
            
            if ($integration) {
                $organization = \App\Models\Organization::find($integration->organization_id);
            }
            
            if (!$organization) {
                return response()->json([
                    'success' => false, 
                    'error' => 'No organization found for WhatsApp phone number ID: ' . $phoneNumberId
                ], 404);
            }
        } else {
            // Fallback: Find the first organization with this channel enabled (for email or other channels)
            $orgIds = AiEmployee::whereJsonContains('allowed_channels', $channel)
                ->pluck('organization_id')
                ->unique()
                ->toArray();

            foreach ($orgIds as $orgId) {
                $org = Organization::find($orgId);
                if ($org && $org->is_active) {
                    $organization = $org;
                    break;
                }
            }
        }

        if (!$organization || !$organization->is_active) {
            return response()->json(['success' => false, 'error' => 'No active organization found'], 404);
        }

        app()->instance('current_organization_id', $organization->id);

        // Find active AI employees in this org that have this channel
        $aiEmployee = $organization->aiEmployees()
            ->where('is_active', true)
            ->whereJsonContains('allowed_channels', $channel)
            ->first();

        if (!$aiEmployee) {
            return response()->json(['success' => false, 'error' => 'No active AI employee with this channel'], 404);
        }

        // Find or create customer
        $customer = $this->conversationService->findOrCreateCustomer($organization, [
            'first_name' => $customerName,
            'email' => $customerEmail,
            'phone' => $customerPhone,
            'channel' => $channel,
            'external_id' => $externalId,
        ]);

        // Find or create conversation
        $conversation = $this->conversationService->findOrCreateConversation(
            $organization,
            $aiEmployee,
            $customer,
            $channel,
            $externalId,
        );

        // Process through AI Orchestrator
        $orchestrator = app(\App\Ai\Orchestrator\AiOrchestrator::class);
        $result = $orchestrator->processIncomingMessage(
            $aiEmployee,
            $conversation,
            $customer,
            $messageContent,
            array_merge($parsed['metadata'] ?? [], ['source' => $channel . '_webhook']),
        );

        // If the AI produced a response, send it back through the channel
        if (! empty($result['response']) && ($result['success'] ?? false)) {
            $recipient = $customerEmail ?? $customerPhone ?? '';
            $subject = $parsed['subject'] ?? null;
            $metadata = array_merge($parsed['metadata'] ?? [], [
                'subject' => $subject ? 'Re: ' . $subject : 'Your inquiry',
                're_subject' => $subject ? 'Re: ' . $subject : null,
            ]);

            $this->channels->sendMessage($channel, $recipient, $result['response'], $metadata);
        }

        return response()->json([
            'success' => $result['success'] ?? false,
            'response' => $result['response'] ?? null,
            'conversation_id' => $conversation->uuid,
            'escalated' => $result['escalated'] ?? false,
        ]);
    }
}