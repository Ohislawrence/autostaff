<?php

namespace Nomdal\Shopify;

class ShopifyClient
{
    private $domain;
    private $accessToken;
    private $apiVersion;
    private $webhookSecret;

    public function __construct(string $domain, string $accessToken, string $apiVersion = '2024-10', string $webhookSecret = '')
    {
        $this->domain = rtrim($domain, '/');
        $this->accessToken = $accessToken;
        $this->apiVersion = $apiVersion;
        $this->webhookSecret = $webhookSecret;
    }

    /**
     * Verify a Shopify webhook using HMAC-SHA256.
     * When no shared secret is configured, verification is skipped.
     */
    public function verifyWebhook(string $rawBody, string $hmacHeader): bool
    {
        if ($this->webhookSecret === '') {
            return true;
        }

        $computed = base64_encode(hash_hmac('sha256', $rawBody, $this->webhookSecret, true));

        return hash_equals($computed, $hmacHeader);
    }

    /**
     * Fetch an order by id (used by the manual backfill script).
     */
    public function getOrder(string $orderId): ?array
    {
        $response = $this->request('GET', sprintf('/admin/api/%s/orders/%s.json', $this->apiVersion, $orderId));

        return $response['order'] ?? null;
    }

    /**
     * Search for a customer by email (used when webhooks deliver only an id).
     */
    public function searchCustomerByEmail(string $email): ?array
    {
        $response = $this->request('GET', sprintf('/admin/api/%s/customers/search.json', $this->apiVersion), [
            'query' => 'email:'.$email,
        ]);

        return $response['customers'][0] ?? null;
    }

    private function request(string $method, string $path, array $query = []): ?array
    {
        $url = 'https://'.$this->domain.$path;

        if (! empty($query)) {
            $url .= (str_contains($url, '?') ? '&' : '?').http_build_query($query);
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CUSTOMREQUEST => strtoupper($method),
            CURLOPT_HTTPHEADER => [
                'X-Shopify-Access-Token: '.$this->accessToken,
                'Content-Type: application/json',
            ],
        ]);

        $raw = curl_exec($ch);
        curl_close($ch);

        if ($raw === false) {
            return null;
        }

        return json_decode($raw, true);
    }
}
