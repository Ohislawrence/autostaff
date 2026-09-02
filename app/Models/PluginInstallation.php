<?php

namespace App\Models;

use App\Tenant\TenantAware;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PluginInstallation extends Model
{
    use HasFactory, TenantAware;

    protected $fillable = [
        'uuid',
        'organization_id',
        'plugin_id',
        'plugin_version_id',
        'site_url',
        'site_label',
        'api_key_id',
        'signing_secret',
        'status',
        'last_seen_at',
    ];

    protected $casts = [
        'last_seen_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (PluginInstallation $installation) {
            if (! $installation->uuid) {
                $installation->uuid = (string) Str::uuid();
            }
        });
    }

    public function plugin()
    {
        return $this->belongsTo(Plugin::class);
    }

    public function pluginVersion()
    {
        return $this->belongsTo(PluginVersion::class);
    }

    public function apiKey()
    {
        return $this->belongsTo(ApiKey::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
