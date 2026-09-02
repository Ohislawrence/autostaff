<?php

namespace App\Models;

use App\Tenant\TenantAware;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ApiKey extends Model
{
    use HasFactory, TenantAware;

    protected $fillable = [
        'organization_id',
        'plugin_installation_id',
        'uuid',
        'name',
        'key',
        'hashed_key',
        'permissions',
        'scopes',
        'last_used_at',
        'expires_at',
        'revoked_at',
        'is_active',
    ];

    protected $casts = [
        'permissions' => 'array',
        'scopes' => 'array',
        'last_used_at' => 'datetime',
        'expires_at' => 'datetime',
        'revoked_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (ApiKey $apiKey) {
            if (! $apiKey->uuid) {
                $apiKey->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * Determine whether this key can currently authenticate requests.
     */
    public function isUsable(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->revoked_at) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * Bump the "last used" timestamp without updating other timestamps.
     */
    public function recordUsage(): void
    {
        $this->newQueryWithoutScopes()
            ->whereKey($this->getKey())
            ->update(['last_used_at' => now()]);
    }

    /**
     * A safe, truncated identifier for display in lists (never the secret).
     */
    public function maskedKey(): string
    {
        return $this->key.'••••••';
    }

    public function pluginInstallation()
    {
        return $this->belongsTo(PluginInstallation::class);
    }
}