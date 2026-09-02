<?php

namespace App\Services\Mcp;

use App\Mcp\McpClient;
use App\Mcp\McpConnection as McpConnectionConfig;
use App\Models\McpConnection as McpConnectionModel;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Http;

class McpConnectionManager
{
    public function forOrganization(int $organizationId): Collection
    {
        return McpConnectionModel::forOrganization($organizationId)->orderBy('provider')->get();
    }

    public function find(int $organizationId, int $id): ?McpConnectionModel
    {
        return McpConnectionModel::forOrganization($organizationId)->find($id);
    }

    public function store(int $organizationId, array $data): McpConnectionModel
    {
        $connection = McpConnectionModel::forOrganization($organizationId)
            ->where('provider', $data['provider'])
            ->first();

        $values = [
            'name' => $data['name'] ?? $data['provider'],
            'transport' => $data['transport'] ?? 'http',
            'endpoint' => $data['endpoint'] ?? null,
            'command' => $data['command'] ?? null,
            'env' => $data['env'] ?? null,
            'headers' => $data['headers'] ?? null,
            'credentials' => $data['credentials'] ?? null,
            'scopes' => $data['scopes'] ?? null,
            'expires_at' => $data['expires_at'] ?? null,
            'timeout' => $data['timeout'] ?? 30,
            'rate_limit' => $data['rate_limit'] ?? null,
            'pricing' => $data['pricing'] ?? null,
            'status' => 'connected',
            'is_connected' => true,
        ];

        if ($connection) {
            $connection->update($values);

            return $connection->fresh();
        }

        return McpConnectionModel::create(array_merge([
            'organization_id' => $organizationId,
            'provider' => $data['provider'],
        ], $values));
    }

    public function buildTransportConfig(McpConnectionModel $connection): McpConnectionConfig
    {
        $credentials = $connection->credentials ?? [];

        if ($connection->transport === 'stdio') {
            return McpConnectionConfig::stdio(
                command: $connection->command ?? [],
                env: array_merge($connection->env ?? [], $this->credentialsAsEnv($credentials)),
                timeout: $connection->timeout ?? 30,
            );
        }

        return McpConnectionConfig::http(
            endpoint: $connection->endpoint ?? '',
            headers: array_merge($connection->headers ?? [], $this->credentialsAsHeaders($credentials)),
            timeout: $connection->timeout ?? 30,
        );
    }

    public function buildClient(McpConnectionModel $connection): McpClient
    {
        return McpClient::forConnection($this->buildTransportConfig($connection));
    }

    public function listTools(McpConnectionModel $connection): array
    {
        return $this->buildClient($connection)->listTools();
    }

    public function healthCheck(McpConnectionModel $connection): array
    {
        try {
            $this->buildClient($connection)->ping();

            $connection->update([
                'status' => 'connected',
                'is_connected' => true,
                'last_health_check_at' => now(),
                'last_error' => null,
            ]);

            return ['success' => true, 'connection_id' => $connection->id];
        } catch (\Throwable $e) {
            $connection->update([
                'status' => 'error',
                'last_health_check_at' => now(),
                'last_error' => $e->getMessage(),
            ]);

            return ['success' => false, 'connection_id' => $connection->id, 'error' => $e->getMessage()];
        }
    }

    public function disconnect(McpConnectionModel $connection): void
    {
        $connection->update([
            'status' => 'disconnected',
            'is_connected' => false,
            'credentials' => null,
            'last_error' => null,
        ]);
    }

    public function refreshTokens(McpConnectionModel $connection, string $tokenUrl): McpConnectionModel
    {
        $credentials = $connection->credentials ?? [];
        $refreshToken = $credentials['refresh_token'] ?? null;

        if (! $refreshToken) {
            throw new \RuntimeException('Connection has no refresh token.');
        }

        $response = Http::asForm()->post($tokenUrl, [
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken,
            'client_id' => $credentials['client_id'] ?? null,
            'client_secret' => $credentials['client_secret'] ?? null,
        ]);

        if ($response->failed()) {
            $connection->update([
                'status' => 'error',
                'last_error' => "Token refresh failed with HTTP {$response->status()}.",
            ]);

            throw new \RuntimeException("Token refresh failed with HTTP {$response->status()}.");
        }

        $data = $response->json();

        $connection->update([
            'credentials' => array_merge($credentials, [
                'access_token' => $data['access_token'] ?? ($credentials['access_token'] ?? null),
                'refresh_token' => $data['refresh_token'] ?? $refreshToken,
            ]),
            'expires_at' => isset($data['expires_in'])
                ? now()->addSeconds((int) $data['expires_in'])
                : $connection->expires_at,
            'status' => 'connected',
            'is_connected' => true,
            'last_error' => null,
        ]);

        return $connection->fresh();
    }

    protected function credentialsAsHeaders(array $credentials): array
    {
        $headers = [];

        if (! empty($credentials['access_token'])) {
            $headers['Authorization'] = 'Bearer ' . $credentials['access_token'];
        }

        if (! empty($credentials['api_key'])) {
            $headers['X-API-Key'] = (string) $credentials['api_key'];
        }

        return $headers;
    }

    protected function credentialsAsEnv(array $credentials): array
    {
        $map = [
            'access_token' => 'MCP_ACCESS_TOKEN',
            'refresh_token' => 'MCP_REFRESH_TOKEN',
            'api_key' => 'MCP_API_KEY',
            'client_id' => 'MCP_CLIENT_ID',
            'client_secret' => 'MCP_CLIENT_SECRET',
        ];

        $env = [];

        foreach ($map as $key => $name) {
            if (! empty($credentials[$key])) {
                $env[$name] = (string) $credentials[$key];
            }
        }

        return $env;
    }
}

