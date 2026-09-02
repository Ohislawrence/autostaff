<?php

namespace App\Http\Middleware\Tenant;

use App\Models\Organization;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ResolveTenantApi
{
    public function handle(Request $request, Closure $next): mixed
    {
        if (! Auth::check()) {
            return $next($request);
        }

        $user = Auth::user();

        // Platform Owner — tenant APIs are not available for platform owners
        if ($user->hasRole('Platform Owner')) {
            return response()->json(['message' => 'Platform Owners cannot access tenant API endpoints.'], 403);
        }

        $organization = null;

        // Allow the API consumer to specify an organization via header
        $orgId = $request->header('X-Organization-Id');
        if ($orgId) {
            $organization = Organization::find($orgId);
            if (! $organization || ! $user->organizations->contains($organization)) {
                return response()->json(['message' => 'Invalid or unauthorized organization.'], 403);
            }
        }

        if (! $organization) {
            $organization = $user->organizations()->first();
        }

        if (! $organization) {
            return response()->json(['message' => 'No organization found. Please create or join an organization.'], 403);
        }

        if (! $organization->is_active) {
            return response()->json(['message' => 'This organization has been suspended.'], 403);
        }

        app()->instance('current_organization', $organization);
        app()->instance('current_organization_id', $organization->id);

        return $next($request);
    }
}