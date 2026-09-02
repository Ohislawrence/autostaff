<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Tool extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid', 'identifier', 'name', 'description', 'input_schema',
        'output_schema', 'handler_class', 'requires_confirmation',
        'category', 'is_custom', 'organization_id', 'custom_config', 'is_active',
    ];

    protected $casts = [
        'input_schema' => 'array',
        'output_schema' => 'array',
        'custom_config' => 'array',
        'requires_confirmation' => 'boolean',
        'is_active' => 'boolean',
        'is_custom' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (Tool $tool) {
            $tool->uuid = (string) Str::uuid();
        });
    }

    public function aiEmployees()
    {
        return $this->belongsToMany(AiEmployee::class, 'ai_employee_tool')
            ->withPivot('is_allowed', 'requires_confirmation', 'max_per_conversation')
            ->withTimestamps();
    }

    public function executions()
    {
        return $this->hasMany(ToolExecution::class);
    }

    /**
     * Scope a query to include global tools and organization-specific tools.
     */
    public function scopeAvailableForOrganization(Builder $query, ?int $organizationId = null): Builder
    {
        return $query->where(function ($q) use ($organizationId) {
            $q->whereNull('organization_id') // Global tools
              ->when($organizationId, function ($qq) use ($organizationId) {
                  $qq->orWhere('organization_id', $organizationId); // Tenant-specific tools
              });
        });
    }

    /**
     * Check if this tool is available for a specific organization.
     */
    public function isAvailableFor(?int $organizationId): bool
    {
        return $this->organization_id === null || $this->organization_id === $organizationId;
    }
}