<?php

namespace App\Http\Middleware\Tenant;

use App\Models\Organization;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ResolveTenant
{
    public function handle(Request $request, Closure $next): mixed
    {
        if (! Auth::check()) {
            return $next($request);
        }

        $user = Auth::user();

        // Platform Owner — redirect to platform dashboard, they shouldn't access tenant routes
        if ($user->hasRole('Platform Owner')) {
            return redirect()->route('platform.dashboard');
        }

        $organization = null;

        if (session()->has('current_organization_id')) {
            $organization = Organization::find(session('current_organization_id'));
        }

        if (! $organization) {
            $organization = $user->organizations()->first();
            if ($organization) {
                session(['current_organization_id' => $organization->id]);
            }
        }

        if ($organization) {
            if (! $user->organizations->contains($organization)) {
                abort(403, 'You do not belong to this organization.');
            }

            if (! $organization->is_active) {
                abort(403, 'This organization has been suspended.');
            }

            app()->instance('current_organization', $organization);
            app()->instance('current_organization_id', $organization->id);

            // Redirect to onboarding if not completed
            if (! $organization->onboarding_completed && ! $request->routeIs('onboarding.*') && ! $request->routeIs('logout')) {
                return redirect()->route('onboarding.show');
            }
        } else {
            // User has no organization — redirect to setup
            return redirect()->route('no-organization');
        }

        return $next($request);
    }
}