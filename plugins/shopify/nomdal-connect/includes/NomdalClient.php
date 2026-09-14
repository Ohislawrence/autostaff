<?php

namespace Nomdal;

class NomdalClient
{
    private $baseUrl;
    private $apiKey;
    private $timeout;
    private $signingSecret;

    public function __construct(string $baseUrl, string $apiKey, int $timeout = 30, string $signingSecret = '')
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->apiKey = $apiKey;
        $this->timeout = $timeout;
        $this->signingSecret = $signingSecret;
    }

    public function request(string $method, string $path, array $body = [], array $query = []): ApiResponse
    {
        $fullPath = '/api/v1'.$path;
        $url = $this->baseUrl.$fullPath;

        if (! empty($query)) {
            $url .= (str_contains($url, '?') ? '&' : '?').http_build_query($query);
        }

        $jsonBody = ! empty($body) ? json_encode($body) : '';

        $headers = [
            'Authorization: Bearer '.$this->apiKey,
            'Accept: application/json',
        ];

        if ($jsonBody !== '') {
            $headers[] = 'Content-Type: application/json';
        }

        if ($this->signingSecret !== '') {
            $timestamp = time();
            $canonical = implode("\n", [strtoupper($method), $fullPath, (string) $timestamp, $jsonBody]);
            $headers[] = 'X-Timestamp: '.$timestamp;
            $headers[] = 'X-Signature: '.hash_hmac('sha256', $canonical, $this->signingSecret);
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_CUSTOMREQUEST => strtoupper($method),
            CURLOPT_HTTPHEADER => $headers,
        ]);

        if ($jsonBody !== '') {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonBody);
        }

        $raw = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($raw === false) {
            return new ApiResponse(false, 0, $error);
        }

        $data = json_decode($raw, true);

        if ($status >= 200 && $status < 300) {
            return new ApiResponse(true, $status, '', $data);
        }

        $message = is_array($data) && isset($data['message']) ? $data['message'] : $raw;

        return new ApiResponse(false, $status, $message, $data);
    }

    public function heartbeat(): ApiResponse
    {
        return $this->request('POST', '/plugins/heartbeat');
    }

    public function createCustomer(array $data): ApiResponse
    {
        return $this->request('POST', '/customers', $data);
    }

    public function findCustomerByEmail(string $email): ApiResponse
    {
        return $this->request('GET', '/customers', [], ['email' => $email]);
    }

    public function createOrder(array $data): ApiResponse
    {
        return $this->request('POST', '/orders', $data);
    }
}
