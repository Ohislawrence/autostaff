<?php

namespace App\Http\Controllers;

use App\Mcp\McpException;
use App\Services\Mcp\McpConnectionManager;
use App\Services\Mcp\McpOAuthService;
use App\Services\Mcp\McpToolRegistrar;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class McpOAuthController extends Controller
{
    public function __construct(
        protected McpOAuthService $oauth,
        protected McpConnectionManager $connections,
        protected McpToolRegistrar $registrar,
    ) {}

    public function redirect(string $provider)
    {
        if (! config('mcp.enabled', true)) {
            return redirect()->route('integrations.mcp.index')->with('error', 'MCP integrations are disabled.');
        }

        if (! in_array($provider, config('mcp.allowed_providers', []), true)) {
            return redirect()->route('integrations.mcp.index')->with('error', 'This provider is not available.');
        }

        $state = Str::random(40);
        session()->put('mcp_oauth_state', $state);
        session()->put('mcp_oauth_provider', $provider);

        return redirect($this->oauth->authorizationUrl($provider, $state));
    }

    public function callback(Request $request)
    {
        $provider = session()->pull('mcp_oauth_provider');
        $expected = session()->pull('mcp_oauth_state');

        if (! $provider || ! $expected || ! hash_equals($expected, (string) $request->get('state'))) {
            return redirect()->route('integrations.mcp.index')->with('error', 'OAuth state mismatch. Please try again.');
        }

        if ($request->has('error')) {
            return redirect()->route('integrations.mcp.index')
                ->with('error', 'OAuth authorization failed: ' . $request->get('error'));
        }

        $code = $request->get('code');
        if (! $code) {
            return redirect()->route('integrations.mcp.index')->with('error', 'OAuth callback missing authorization code.');
        }

        try {
            $tokens = $this->oauth->exchangeCode($provider, $code);
        } catch (McpException $e) {
            return redirect()->route('integrations.mcp.index')->with('error', $e->getMessage());
        }

        $config = $this->oauth->config($provider);

        $connection = $this->connections->store(current_org()->id, [
            'provider' => $provider,
            'name' => ucfirst(str_replace('_', ' ', $provider)),
            'transport' => 'http',
            'endpoint' => $this->oauth->endpoint($provider),
            'credentials' => [
                'access_token' => $tokens['access_token'] ?? null,
                'refresh_token' => $tokens['refresh_token'] ?? null,
                'client_id' => $config['client_id'],
                'client_secret' => $config['client_secret'],
            ],
            'scopes' => $this->oauth->scopes($provider),
            'expires_at' => isset($tokens['expires_in']) ? now()->addSeconds((int) $tokens['expires_in']) : null,
        ]);

        try {
            $count = $this->registrar->sync($connection);
        } catch (\Throwable $e) {
            return redirect()->route('integrations.mcp.index')
                ->with('error', 'Connected, but tool sync failed: ' . $e->getMessage());
        }

        return redirect()->route('integrations.mcp.index')
            ->with('success', "{$connection->name} connected with {$count} tools synced.");
    }
}
