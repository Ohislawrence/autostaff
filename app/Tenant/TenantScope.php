<?php

namespace App\Tenant;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class TenantScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        if (! $this->shouldApply()) {
            return;
        }

        $organizationId = $this->resolveOrganizationId();

        if ($organizationId) {
            // Only scope by organization_id, don't exclude templates here
            // Templates have null organization_id and will be excluded automatically
            $builder->where($model->getTable() . '.organization_id', $organizationId);
        }
    }

    /**
     * Extend the query builder with the needed functions.
     */
    public function extend(Builder $builder): void
    {
        $builder->macro('withoutTenancy', function (Builder $builder) {
            return $builder->withoutGlobalScope(static::class);
        });

        $builder->macro('forOrganization', function (Builder $builder, $organizationId) {
            return $builder->withoutGlobalScope(static::class)
                ->where('organization_id', $organizationId);
        });
    }

    protected function shouldApply(): bool
    {
        // Skip if running in console (queue workers need explicit context)
        if (app()->runningInConsole() && ! app()->has('current_organization_id')) {
            return false;
        }

        return true;
    }

    protected function resolveOrganizationId(): ?int
    {
        // Priority: explicit set in container > auth user's current org
        if (app()->has('current_organization_id')) {
            return app('current_organization_id');
        }

        if (Auth::check() && session()->has('current_organization_id')) {
            return session('current_organization_id');
        }

        return null;
    }
}