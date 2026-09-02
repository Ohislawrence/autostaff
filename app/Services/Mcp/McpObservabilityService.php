<?php

namespace App\Services\Mcp;

use App\Models\McpConnection;
use App\Models\Tool;
use App\Models\ToolExecution;

class McpObservabilityService
{
    public function summary(int $organizationId): array
    {
        $connections = McpConnection::forOrganization($organizationId)
            ->orderBy('provider')
            ->get();

        $toolCounts = Tool::query()
            ->where('organization_id', $organizationId)
            ->where('is_custom', true)
            ->get()
            ->groupBy(fn (Tool $tool) => $tool->custom_config['mcp_connection_id'] ?? null);

        return $connections->map(function (McpConnection $connection) use ($toolCounts) {
            $expiresAt = $connection->expires_at;

            return [
                'id' => $connection->id,
                'provider' => $connection->provider,
                'name' => $connection->name,
                'status' => $connection->status,
                'is_connected' => $connection->is_connected,
                'tool_count' => $toolCounts->get($connection->id)?->count() ?? 0,
                'expires_at' => $expiresAt?->toIso8601String(),
                'expiring_soon' => $expiresAt !== null && $expiresAt->lte(now()->addDays(7)),
                'last_health_check_at' => $connection->last_health_check_at?->toIso8601String(),
                'last_error' => $connection->last_error,
            ];
        })->values()->toArray();
    }

    public function recentCalls(int $organizationId, int $limit = 20): array
    {
        return ToolExecution::forOrganization($organizationId)
            ->whereHas('tool', fn ($q) => $q->where('is_custom', true)->where('category', 'external'))
            ->with('tool:id,identifier,name')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(fn (ToolExecution $execution) => [
                'id' => $execution->id,
                'tool' => $execution->tool?->name,
                'status' => $execution->status,
                'execution_time_ms' => $execution->execution_time_ms,
                'estimated_cost' => (float) $execution->estimated_cost,
                'created_at' => $execution->created_at?->toIso8601String(),
                'error_message' => $execution->error_message,
            ])
            ->toArray();
    }
}
