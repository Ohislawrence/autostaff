<?php

namespace App\Http\Middleware;

use App\Models\ApiKey;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthenticateApiKey
{
    public function handle(Request $request, Closure $next): mixed
    {
        $token = $this->bearerToken($request);

        if (! $token) {
            return response()->json(['message' => 'Missing API key.'], 401);
        }

        $apiKey = $this->resolveApiKey($token);

        if (! $apiKey || ! $apiKey->isUsable()) {
            return response()->json(['message' => 'Invalid or inactive API key.'], 401);
        }

        $organization = $apiKey->organization;

        if (! $organization || ! $organization->is_active) {
            return response()->json(['message' => 'This organization has been suspended.'], 403);
        }

        $apiKey->recordUsage();

        app()->instance('current_api_key', $apiKey);
        app()->instance('current_organization', $organization);
        app()->instance('current_organization_id', $organization->id);

        return $next($request);
    }

    protected function bearerToken(Request $request): ?string
    {
        $header = $request->header('Authorization', '');

        if (str_starts_with(strtolower($header), 'bearer ')) {
            return trim(substr($header, 7));
        }

        return null;
    }

    protected function resolveApiKey(string $token): ?ApiKey
    {
        $parts = explode('.', $token, 2);

        if (count($parts) !== 2 || $parts[0] === '' || $parts[1] === '') {
            return null;
        }

        $apiKey = ApiKey::query()
            ->withoutGlobalScopes()
            ->where('key', $parts[0])
            ->first();

        if (! $apiKey) {
            return null;
        }

        return Hash::check($token, $apiKey->hashed_key) ? $apiKey : null;
    }
}
