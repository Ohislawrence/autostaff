<?php

namespace App\Services\Mcp;

use App\Mcp\McpException;
use Illuminate\Support\Facades\Http;

class McpOAuthService
{
    public function driverFor(string $provider): string
    {
        return in_array($provider, ['google_calendar', 'gmail'], true) ? 'google' : $provider;
    }

    public function config(string $provider): array
    {
        $driver = $this->driverFor($provider);
        $config = config("mcp.oauth.{$driver}");

        if (! $config || empty($config['client_id']) || empty($config['client_secret'])) {
            throw new McpException("OAuth is not configured for '{$provider}'.");
        }

        return $config;
    }

    public function scopes(string $provider): array
    {
        $config = $this->config($provider);

        return $config['scopes'][$provider] ?? $config['scopes'] ?? [];
    }

    public function endpoint(string $provider): string
    {
        return $this->config($provider)['endpoint'];
    }

    public function authorizationUrl(string $provider, string $state): string
    {
        $config = $this->config($provider);

        $query = array_merge([
            'client_id' => $config['client_id'],
            'redirect_uri' => $config['redirect_uri'],
            'response_type' => 'code',
            'scope' => implode(' ', $this->scopes($provider)),
            'state' => $state,
        ], $config['authorize_params'] ?? []);

        return $config['authorize_url'] . '?' . http_build_query($query);
    }

    public function exchangeCode(string $provider, string $code): array
    {
        $config = $this->config($provider);

        $response = Http::asForm()->post($config['token_url'], [
            'code' => $code,
            'client_id' => $config['client_id'],
            'client_secret' => $config['client_secret'],
            'redirect_uri' => $config['redirect_uri'],
            'grant_type' => 'authorization_code',
        ]);

        if ($response->failed()) {
            throw new McpException('OAuth token exchange failed with HTTP ' . $response->status() . '.');
        }

        $data = $response->json();

        if (empty($data['access_token'])) {
            throw new McpException('OAuth token response did not include an access token.');
        }

        return $data;
    }
}
