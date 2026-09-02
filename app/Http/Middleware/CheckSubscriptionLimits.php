<?php

namespace App\Http\Middleware;

use App\Services\Billing\UsageTracker;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSubscriptionLimits
{
    public function __construct(protected UsageTracker $usageTracker) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $resource): Response
    {
        $organization = current_org();

        if (!$organization) {
            return $next($request);
        }

        // Check if at limit
        if ($this->usageTracker->isAtLimit($organization, $resource)) {
            $upgradePlan = $this->usageTracker->getUpgradePlan($organization);
            
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => "You've reached your plan limit for {$resource}.",
                    'limit_type' => $resource,
                    'upgrade_available' => $upgradePlan !== null,
                    'upgrade_plan' => $upgradePlan ? $upgradePlan['plan']->name : null,
                ], 403);
            }

            return back()->with('error', "You've reached your plan limit for {$resource}. Please upgrade to continue.");
        }

        return $next($request);
    }
}
