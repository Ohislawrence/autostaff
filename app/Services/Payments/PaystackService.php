<?php

namespace App\Services\Payments;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaystackService
{
    protected string $secretKey;
    protected string $baseUrl = 'https://api.paystack.co';

    public function __construct()
    {
        $this->secretKey = config('services.paystack.secret_key', env('PAYSTACK_SECRET_KEY', ''));
    }

    /**
     * Generate a payment link for an invoice.
     */
    public function generatePaymentLink(array $data): array
    {
        if (empty($this->secretKey)) {
            return ['success' => false, 'error' => 'Paystack is not configured. Set PAYSTACK_SECRET_KEY in .env'];
        }

        try {
            $response = Http::withToken($this->secretKey)
                ->post("{$this->baseUrl}/transaction/initialize", [
                    'email' => $data['email'],
                    'amount' => (int) round($data['amount'] * 100), // Paystack expects kobo
                    'currency' => $data['currency'] ?? 'NGN',
                    'reference' => $data['reference'],
                    'metadata' => [
                        'invoice_id' => $data['invoice_id'] ?? null,
                        'order_id' => $data['order_id'] ?? null,
                        'organization_id' => $data['organization_id'] ?? null,
                    ],
                    'callback_url' => $data['callback_url'] ?? null,
                ]);

            if ($response->successful() && ($response['status'] ?? false)) {
                return [
                    'success' => true,
                    'reference' => $response['data']['reference'],
                    'authorization_url' => $response['data']['authorization_url'],
                    'access_code' => $response['data']['access_code'],
                ];
            }

            Log::error('Paystack payment link failed', ['response' => $response->body()]);
            return ['success' => false, 'error' => $response['message'] ?? 'Failed to generate payment link'];

        } catch (\Exception $e) {
            Log::error('Paystack service error', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Verify a Paystack transaction reference.
     */
    public function verifyTransaction(string $reference): array
    {
        if (empty($this->secretKey)) {
            return ['success' => false, 'error' => 'Paystack is not configured.'];
        }

        try {
            $response = Http::withToken($this->secretKey)
                ->get("{$this->baseUrl}/transaction/verify/{$reference}");

            if ($response->successful() && ($response['status'] ?? false)) {
                $data = $response['data'];
                $amount = ($data['amount'] ?? 0) / 100; // Convert from kobo

                return [
                    'success' => true,
                    'verified' => $data['status'] === 'success',
                    'reference' => $data['reference'],
                    'amount' => $amount,
                    'currency' => $data['currency'] ?? 'NGN',
                    'gateway_response' => $data['gateway_response'] ?? '',
                    'paid_at' => $data['paid_at'] ?? null,
                    'channel' => $data['channel'] ?? '',
                    'metadata' => $data['metadata'] ?? [],
                ];
            }

            return ['success' => false, 'error' => $response['message'] ?? 'Verification failed'];

        } catch (\Exception $e) {
            Log::error('Paystack verification error', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Generate a unique transaction reference.
     */
    public function generateReference(string $prefix = 'AST'): string
    {
        return strtoupper($prefix . '_' . now()->format('Ymd') . '_' . bin2hex(random_bytes(4)));
    }

    public function isConfigured(): bool
    {
        return ! empty($this->secretKey);
    }
}