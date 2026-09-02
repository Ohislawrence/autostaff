<?php

namespace App\Http\Controllers;

use App\Models\Organization;

abstract class Controller
{
    /**
     * Get the current organization from the session/container.
     * Returns null if no organization is resolved (Platform Owner or no-org users).
     */
    protected function currentOrganization(): ?Organization
    {
        // Try container first (set by ResolveTenant middleware)
        if (app()->bound('current_organization')) {
            return app('current_organization');
        }

        // Fallback: check session
        $orgId = session('current_organization_id');
        if ($orgId) {
            $org = Organization::find($orgId);
            if ($org) {
                app()->instance('current_organization', $org);
                app()->instance('current_organization_id', $org->id);
            }
            return $org;
        }

        return null;
    }

    /**
     * Helper to get the current organization ID.
     */
    protected function currentOrganizationId(): ?int
    {
        return $this->currentOrganization()?->id;
    }
}