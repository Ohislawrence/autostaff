<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Billing\SubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class NombaWebhookController extends Controller
{
    public function __invoke(Request $request, SubscriptionService $subscriptions)
    {
        $secret = config('services.nomba.webhook_secret');

        if ($secret && ! hash_equals($secret, (string) ($request->header('X-Nomba-Signature') ?? ''))) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $payload = $request->all();
        $event = strtolower((string) ($payload['event'] ?? $payload['type'] ?? ''));
        $reference = $payload['data']['reference'] ?? ($payload['reference'] ?? null);

        Log::info('Nomba webhook received', ['event' => $event, 'reference' => $reference]);

        $successEvents = ['transaction.success', 'payment.success', 'charge.success', 'checkout.completed', 'success', 'payment_successful'];
        $failureEvents = ['transaction.failed', 'payment.failed', 'charge.failed', 'checkout.failed', 'failed'];

        if ($reference && in_array($event, $successEvents, true)) {
            $subscriptions->activateFromReference($reference);
        } elseif ($reference && in_array($event, $failureEvents, true)) {
            $subscriptions->recordFailureFromReference($reference, 'Payment failed (webhook)');
        }

        return response()->json(['status' => 'ok']);
    }
}
