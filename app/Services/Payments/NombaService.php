<?php

namespace App\Services\Payments;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Nomba (formerly Kudi) payments integration.
 *
 * Nomba "Accept Payments" supports card + bank transfer via their Checkout
 * and Charge APIs. This service implements the Checkout flow:
 *   - initialize a checkout order (returns a hosted checkout URL)
 *   - verify a transaction by reference
 *
 * NOTE: field names/endpoints follow Nomba's current API reference. If your
 * Nomba account uses a different environment, override NOMBA_BASE_URL and
 * confirm the checkout/verify paths against your dashboard docs.
 */
class NombaService
{
    protected string $secretKey;
    protected string $accountId;
    protected string $baseUrl;

    public function __construct()
    {
        $this->secretKey = (string) (config('services.nomba.secret_key') ?? '');
        $this->accountId = (string) (config('services.nomba.account_id') ?? '');
        $this->baseUrl = rtrim((string) (config('services.nomba.base_url') ?? 'https://api.nomba.com'), '/') ?: 'https://api.nomba.com';
    }

    public function isConfigured(): bool
    {
        return ! empty($this->secretKey) && ! empty($this->accountId);
    }

    /**
     * Create a hosted checkout to collect a one-off payment.
     */
    public function initializeCheckout(array $data): array
    {
        if (! $this->isConfigured()) {
            return ['success' => false, 'error' => 'Nomba is not configured. Set NOMBA_SECRET_KEY and NOMBA_ACCOUNT_ID in .env'];
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->secretKey,
                'accountId' => $this->accountId,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->post("{$this->baseUrl}/v1/checkout/order", [
                'amount' => (int) round(($data['amount'] ?? 0) * 100), // Nomba expects kobo (minor unit)
                'currency' => $data['currency'] ?? 'NGN',
                'reference' => $data['reference'],
                'customer_email' => $data['email'] ?? null,
                'customer_name' => $data['name'] ?? null,
                'redirect_url' => $data['callback_url'] ?? null,
                'description' => $data['description'] ?? 'Subscription payment',
                'metadata' => $data['metadata'] ?? [],
            ]);

            if ($response->successful()) {
                $body = $response->json();
                $checkoutUrl = $body['data']['checkout_url']
                    ?? $body['data']['checkoutUrl']
                    ?? $body['data']['payment_link']
                    ?? $body['data']['paymentLink']
                    ?? $body['checkout_url']
                    ?? null;

                if ($checkoutUrl) {
                    return [
                        'success' => true,
                        'reference' => $body['data']['reference'] ?? $data['reference'],
                        'checkout_url' => $checkoutUrl,
                    ];
                }
            }

            Log::error('Nomba checkout failed', ['status' => $response->status(), 'body' => $response->body()]);

            return ['success' => false, 'error' => $response->json('message') ?? 'Failed to initialize Nomba checkout.'];

        } catch (\Throwable $e) {
            Log::error('Nomba checkout error', ['error' => $e->getMessage()]);

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Verify a transaction by its reference.
     */
    public function verifyTransaction(string $reference): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->secretKey,
                'accountId' => $this->accountId,
                'Accept' => 'application/json',
            ])->get("{$this->baseUrl}/v1/transaction/{$reference}");

            if (! $response->successful()) {
                return ['success' => false, 'error' => $response->json('message') ?? 'Verification failed'];
            }

            $body = $response->json();
            $data = $body['data'] ?? $body;

            $status = strtolower((string) ($data['status'] ?? $data['transaction_status'] ?? ''));

            return [
                'success' => true,
                'verified' => in_array($status, ['success', 'successful', 'completed', 'paid'], true),
                'status' => $status,
                'reference' => $data['reference'] ?? $reference,
                'amount' => ($data['amount'] ?? 0) / 100,
                'currency' => $data['currency'] ?? 'NGN',
                'gateway_response' => $data['gateway_response'] ?? ($data['message'] ?? ''),
            ];

        } catch (\Throwable $e) {
            Log::error('Nomba verification error', ['reference' => $reference, 'error' => $e->getMessage()]);

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function generateReference(string $prefix = 'NOM'): string
    {
        return strtoupper($prefix . '_' . now()->format('Ymd') . '_' . bin2hex(random_bytes(4)));
    }
}
