<?php

namespace App\Http\Middleware;

use App\Services\Affiliate\AffiliateTrackingService;
use Closure;
use Illuminate\Http\Request;

class CaptureAffiliateClick
{
    public function handle(Request $request, Closure $next): mixed
    {
        try {
            app(AffiliateTrackingService::class)->captureClick($request);
        } catch (\Throwable $e) {
            // Affiliate tracking must never break a request.
        }

        return $next($request);
    }
}
