<?php

namespace Nomdal;

class ApiClient
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

    /**
     * Build a client from the stored settings, or null if unconfigured.
     */
    public static function fromSettings(): ?self
    {
        $baseUrl = get_option('nomdal_base_url', '');
        $apiKey = get_option('nomdal_api_key', '');

        if (empty($baseUrl) || empty($apiKey)) {
            return null;
        }

        return new self($baseUrl, $apiKey, 30, (string) get_option('nomdal_signing_secret', ''));
    }

    public function request(string $method, string $path, array $body = [], array $query = []): ApiResponse
    {
        $fullPath = '/api/v1'.$path;
        $url = $this->baseUrl.$fullPath;

        if (! empty($query)) {
            $url = add_query_arg($query, $url);
        }

        $args = [
            'method' => strtoupper($method),
            'timeout' => $this->timeout,
            'headers' => [
                'Authorization' => 'Bearer '.$this->apiKey,
                'Accept' => 'application/json',
            ],
        ];

        $jsonBody = '';
        if (! empty($body)) {
            $args['headers']['Content-Type'] = 'application/json';
            $jsonBody = wp_json_encode($body);
            $args['body'] = $jsonBody;
        }

        if ($this->signingSecret !== '') {
            $timestamp = time();
            $canonical = implode("\n", [strtoupper($method), $fullPath, (string) $timestamp, $jsonBody]);
            $args['headers']['X-Timestamp'] = (string) $timestamp;
            $args['headers']['X-Signature'] = hash_hmac('sha256', $canonical, $this->signingSecret);
        }

        $response = wp_remote_request($url, $args);

        if (is_wp_error($response)) {
            return new ApiResponse(false, 0, $response->get_error_message());
        }

        $status = (int) wp_remote_retrieve_response_code($response);
        $raw = wp_remote_retrieve_body($response);
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

    public function listAiEmployees(): ApiResponse
    {
        return $this->request('GET', '/ai-employees');
    }

    public function createLead(array $data): ApiResponse
    {
        return $this->request('POST', '/leads', $data);
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
