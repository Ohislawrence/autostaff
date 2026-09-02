<?php

namespace App\Services\Mcp;

use App\Mcp\McpException;
use App\Mcp\McpTool;
use App\Models\McpConnection as McpConnectionModel;
use Illuminate\Support\Facades\RateLimiter;
use Throwable;

/**
 * Guards and executes an MCP tools/call for a tenant connection.
 *
 * Enforces (in order): input validation, per-tenant rate limiting, circuit
 * breaker, then retries the call on transient transport failures.
 */
class McpToolInvoker
{
    public function __construct(
        protected McpConnectionManager $connections,
        protected int $maxAttempts = 3,
        protected int $retryDelayMs = 100,
        protected int $circuitThreshold = 3,
        protected int $circuitCooldownSeconds = 60,
    ) {}

    public function call(McpConnectionModel $connection, McpTool $tool, array $arguments): array
    {
        $this->validateArguments($tool, $arguments);
        $this->assertRateLimit($connection);
        $this->assertCircuitClosed($connection);

        $client = $this->connections->buildClient($connection);

        $lastError = null;

        for ($attempt = 1; $attempt <= $this->maxAttempts; $attempt++) {
            try {
                $result = $client->callTool($tool->name, $arguments);
                $this->markSuccess($connection);

                return $result;
            } catch (Throwable $e) {
                $lastError = $e;

                if (! $this->isRetryable($e) || $attempt === $this->maxAttempts) {
                    break;
                }

                usleep($this->retryDelayMs * 1000 * $attempt);
            }
        }

        $message = $lastError?->getMessage() ?? 'MCP tool call failed.';
        $this->markFailure($connection, $message);

        throw new McpException($message);
    }

    protected function validateArguments(McpTool $tool, array $arguments): void
    {
        $errors = $this->schemaErrors($tool->inputSchema ?: ['type' => 'object'], $arguments);

        if ($errors) {
            throw new McpException('Invalid tool arguments: ' . implode('; ', $errors));
        }
    }

    protected function assertRateLimit(McpConnectionModel $connection): void
    {
        $key = "mcp:{$connection->organization_id}:{$connection->provider}";

        if (RateLimiter::tooManyAttempts($key, $this->rateLimitPerMinute($connection))) {
            throw new McpException("MCP rate limit exceeded for {$connection->provider}.");
        }

        RateLimiter::hit($key, 60);
    }

    protected function assertCircuitClosed(McpConnectionModel $connection): void
    {
        if ($connection->circuit_open_until && $connection->circuit_open_until->isFuture()) {
            throw new McpException('MCP circuit is open; requests are temporarily paused.');
        }
    }

    protected function markSuccess(McpConnectionModel $connection): void
    {
        $connection->update([
            'consecutive_failures' => 0,
            'circuit_open_until' => null,
            'status' => 'connected',
            'is_connected' => true,
            'last_error' => null,
        ]);
    }

    protected function markFailure(McpConnectionModel $connection, string $message): void
    {
        $failures = ($connection->consecutive_failures ?? 0) + 1;
        $openUntil = $failures >= $this->circuitThreshold
            ? now()->addSeconds($this->circuitCooldownSeconds)
            : null;

        $connection->update([
            'consecutive_failures' => $failures,
            'circuit_open_until' => $openUntil,
            'status' => $openUntil ? 'error' : $connection->status,
            'last_error' => $message,
        ]);
    }

    protected function isRetryable(Throwable $e): bool
    {
        // Retry transport/network errors (no JSON-RPC error code), not application errors.
        if ($e instanceof McpException) {
            return $e->getCode() === 0;
        }

        return true;
    }

    protected function rateLimitPerMinute(McpConnectionModel $connection): int
    {
        $config = $connection->rate_limit ?? [];

        return (int) ($config['max_per_minute'] ?? 60);
    }

    protected function schemaErrors(array $schema, array $arguments): array
    {
        $errors = [];

        foreach (($schema['required'] ?? []) as $field) {
            if (! array_key_exists($field, $arguments)) {
                $errors[] = "Missing required field '{$field}'";
            }
        }

        foreach (($schema['properties'] ?? []) as $field => $prop) {
            if (! array_key_exists($field, $arguments)) {
                continue;
            }

            $expected = $prop['type'] ?? null;
            if ($expected && ! $this->typeMatches($expected, $arguments[$field])) {
                $errors[] = "Field '{$field}' must be of type {$expected}";
            }
        }

        return $errors;
    }

    protected function typeMatches(string $expected, mixed $value): bool
    {
        return match ($expected) {
            'string' => is_string($value),
            'integer' => is_int($value),
            'number' => is_int($value) || is_float($value),
            'boolean' => is_bool($value),
            'array' => is_array($value),
            'object' => is_array($value) || is_object($value),
            'null' => $value === null,
            default => true,
        };
    }
}
