<?php

namespace App\Http\Middleware;

use App\Models\ApiKey;
use Closure;
use Illuminate\Http\Request;

class CheckApiKeyScope
{
    public function handle(Request $request, Closure $next, string ...$requiredScopes): mixed
    {
        $apiKey = app('current_api_key');

        if (! $apiKey instanceof ApiKey) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $granted = $apiKey->scopes ?? [];

        foreach ($requiredScopes as $scope) {
            if (in_array('*', $granted, true) || in_array($scope, $granted, true)) {
                continue;
            }

            return response()->json(['message' => "Missing required scope: {$scope}"], 403);
        }

        return $next($request);
    }
}
