<?php

namespace App\Models;

use App\Tenant\TenantAware;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Integration extends Model
{
    use HasFactory, TenantAware;

    protected $fillable = [
        'uuid',
        'organization_id',
        'provider',
        'name',
        'config',
        'is_connected',
        'status',
        'last_synced_at',
    ];

    protected $casts = [
        'config' => 'array',
        'is_connected' => 'boolean',
        'last_synced_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Integration $integration) {
            $integration->uuid = (string) Str::uuid();
        });
    }

    public const SENSITIVE_CONFIG_KEYS = [
        'consumer_key',
        'consumer_secret',
        'webhook_secret',
        'access_token',
        'api_key',
        'secret',
        'password',
    ];

    /**
     * Get a config value, transparently decrypting values stored with an
     * "encrypted:" prefix.
     */
    public function getConfigValue(string $key, mixed $default = null): mixed
    {
        $value = $this->config[$key] ?? $default;

        if (is_string($value) && str_starts_with($value, 'encrypted:')) {
            try {
                return decrypt(substr($value, 10));
            } catch (\Throwable) {
                return $value;
            }
        }

        return $value;
    }

    /**
     * Encrypt a sensitive config value for storage.
     */
    public static function encryptConfigValue(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }

        if (str_starts_with($value, 'encrypted:')) {
            return $value;
        }

        return 'encrypted:'.encrypt($value);
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function credentials()
    {
        return $this->hasMany(IntegrationCredential::class);
    }
}