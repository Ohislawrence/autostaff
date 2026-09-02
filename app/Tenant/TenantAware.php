<?php

namespace App\Tenant;

use Illuminate\Database\Eloquent\Builder;

trait TenantAware
{
    public static function bootTenantAware(): void
    {
        static::addGlobalScope(new TenantScope);

        static::creating(function ($model) {
            // Don't auto-assign organization_id for templates
            if (isset($model->is_template) && $model->is_template) {
                return;
            }
            
            if (! $model->organization_id && app()->has('current_organization_id')) {
                $model->organization_id = app('current_organization_id');
            }
        });
    }

    /**
     * Scope a query to only include models for a specific organization.
     */
    public function scopeForOrganization(Builder $query, int $organizationId): Builder
    {
        return $query->withoutGlobalScope(TenantScope::class)
            ->where('organization_id', $organizationId);
    }

    /**
     * Get the organization that owns this model.
     */
    public function organization()
    {
        return $this->belongsTo(\App\Models\Organization::class);
    }
}