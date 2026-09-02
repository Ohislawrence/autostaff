<?php

namespace App\Jobs\Traits;

use App\Jobs\Middleware\TenantMiddleware;

trait TenantAwareJob
{
    /**
     * The organization ID to restore context for.
     */
    protected int $tenantOrganizationId;

    /**
     * Attach the tenant middleware so context is automatically restored.
     */
    public function middleware(): array
    {
        return [new TenantMiddleware];
    }

    /**
     * Tag the payload with the current organization ID.
     * Call this from the dispatching code (controller / listener) before dispatch.
     *
     * Usage: $job = new SomeJob($data); $job->withTenant(app('current_organization_id'));
     */
    public function withTenant(int $organizationId): static
    {
        $this->tenantOrganizationId = $organizationId;

        return $this;
    }

    /**
     * Convenience method: set the tenant from the current request context.
     */
    public function withCurrentTenant(): static
    {
        if (app()->has('current_organization_id')) {
            $this->tenantOrganizationId = (int) app('current_organization_id');
        }

        return $this;
    }
}