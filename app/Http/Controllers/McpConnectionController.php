<?php

namespace App\Http\Controllers;

use App\Mcp\McpException;
use App\Models\McpConnection;
use App\Services\Mcp\McpConnectionManager;
use App\Services\Mcp\McpEndpointValidator;
use App\Services\Mcp\McpObservabilityService;
use App\Services\Mcp\McpToolRegistrar;
use Illuminate\Http\Request;
use Inertia\Inertia;

class McpConnectionController extends Controller
{
    public function __construct(
        protected McpConnectionManager $connections,
        protected McpToolRegistrar $registrar,
        protected McpObservabilityService $observability,
        protected McpEndpointValidator $endpointValidator,
    ) {}

    public function index()
    {
        $org = current_org();

        return Inertia::render('Integrations/Mcp', [
            'connections' => $this->observability->summary($org->id),
            'recentCalls' => $this->observability->recentCalls($org->id),
        ]);
    }

    public function store(Request $request)
    {
        if (! config('mcp.enabled', true)) {
            return redirect()->route('integrations.mcp.index')
                ->with('error', 'MCP integrations are currently disabled.');
        }

        $org = current_org();

        $validated = $request->validate([
            'provider' => 'required|string|max:100',
            'name' => 'nullable|string|max:255',
            'transport' => 'nullable|in:http,stdio',
            'endpoint' => 'nullable|string|url',
            'credentials' => 'nullable|array',
            'pricing' => 'nullable|array',
            'rate_limit' => 'nullable|array',
        ]);

        if (! in_array($validated['provider'], config('mcp.allowed_providers', []), true)) {
            return redirect()->route('integrations.mcp.index')
                ->with('error', 'This MCP provider is not available yet.');
        }

        if (! empty($validated['endpoint'])) {
            try {
                $this->endpointValidator->assertSafe($validated['endpoint']);
            } catch (McpException $e) {
                return redirect()->route('integrations.mcp.index')
                    ->with('error', $e->getMessage());
            }
        }

        $connection = $this->connections->store($org->id, $validated);

        try {
            $count = $this->registrar->sync($connection);
        } catch (\Throwable $e) {
            return redirect()->route('integrations.mcp.index')
                ->with('error', 'Connection saved, but tool sync failed: ' . $e->getMessage());
        }

        return redirect()->route('integrations.mcp.index')
            ->with('success', "{$connection->name} connected with {$count} tools synced.");
    }

    public function disconnect(McpConnection $connection)
    {
        $this->authorizeConnection($connection);

        $this->registrar->removeForConnection($connection);
        $this->connections->disconnect($connection);

        return redirect()->route('integrations.mcp.index')
            ->with('success', 'MCP connection disconnected.');
    }

    public function healthCheck(McpConnection $connection)
    {
        $this->authorizeConnection($connection);

        $result = $this->connections->healthCheck($connection);

        return redirect()->route('integrations.mcp.index')->with(
            $result['success'] ? 'success' : 'error',
            $result['success']
                ? 'Connection is healthy.'
                : 'Health check failed: ' . ($result['error'] ?? 'unknown error'),
        );
    }

    public function sync(McpConnection $connection)
    {
        $this->authorizeConnection($connection);

        $count = $this->registrar->sync($connection);

        return redirect()->route('integrations.mcp.index')
            ->with('success', "{$count} tools synced.");
    }

    protected function authorizeConnection(McpConnection $connection): void
    {
        abort_unless($connection->organization_id === current_org()?->id, 403);
    }
}
