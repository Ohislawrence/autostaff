<?php

namespace App\Jobs\Middleware;

use Closure;

class TenantMiddleware
{
    /**
     * Process the queued job with tenant context restored.
     *
     * Looks for $job->tenantOrganizationId and sets it into the container
     * before the job handles, then removes it afterwards.
     */
    public function handle(object $job, Closure $next): void
    {
        $organizationId = $job->tenantOrganizationId ?? null;

        if ($organizationId) {
            app()->instance('current_organization_id', (int) $organizationId);
        }

        try {
            $next($job);
        } finally {
            if ($organizationId) {
                app()->forgetInstance('current_organization_id');
            }
        }
    }
}