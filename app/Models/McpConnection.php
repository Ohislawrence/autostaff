<?php

namespace App\Models;

use App\Tenant\TenantAware;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class McpConnection extends Model
{
    use HasFactory, TenantAware;

    protected $fillable = [
        'uuid', 'organization_id', 'provider', 'name', 'transport', 'endpoint',
        'command', 'env', 'headers', 'credentials', 'scopes', 'expires_at',
        'timeout', 'status', 'is_connected', 'last_health_check_at', 'last_error', 'rate_limit',
        'pricing', 'consecutive_failures', 'circuit_open_until',
    ];

    protected $casts = [
        'command' => 'array',
        'env' => 'array',
        'headers' => 'array',
        'credentials' => 'encrypted:array',
        'scopes' => 'array',
        'rate_limit' => 'array',
        'pricing' => 'array',
        'expires_at' => 'datetime',
        'last_health_check_at' => 'datetime',
        'timeout' => 'integer',
        'is_connected' => 'boolean',
        'consecutive_failures' => 'integer',
        'circuit_open_until' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (McpConnection $connection) {
            $connection->uuid = (string) Str::uuid();
        });
    }
}
