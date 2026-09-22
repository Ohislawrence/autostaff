<?php

namespace App\Http\Middleware;

use App\Services\Prospecting\ProspectingPlanGate;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckProspectingAccess
{
    public function __construct(protected ProspectingPlanGate $gate) {}

    public function handle(Request $request, Closure $next): Response
    {
        $organization = current_org();

        if ($organization && ! $this->gate->isAllowed($organization)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Prospecting is not available on your current plan.',
                    'upgrade_required' => true,
                ], 403);
            }

            return back()->with('error', 'Prospecting is available on the Business plan and above. Please upgrade to continue.');
        }

        return $next($request);
    }
}
