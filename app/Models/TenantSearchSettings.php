<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Per-tenant web search provider configuration.
 *
 * Allowed `provider` values:
 *  - `platform` — use the platform-level shared search (no tenant key required).
 *  - `serper`   — bring-your-own Serper.dev key.
 *  - `brave`    — bring-your-own Brave Search key.
 *  - `none`     — disable live search (AI-generated suggestions only).
 *
 * The API key is encrypted at rest (mirrors McpConnection credentials).
 */
class TenantSearchSettings extends Model
{
    public const PROVIDER_PLATFORM = 'platform';
    public const PROVIDER_SERPER = 'serper';
    public const PROVIDER_BRAVE = 'brave';
    public const PROVIDER_NONE = 'none';

    protected $fillable = ['organization_id', 'provider', 'api_key'];

    protected $casts = [
        'api_key' => 'encrypted',
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public static function forOrganization(int $organizationId): self
    {
        return static::firstOrCreate(
            ['organization_id' => $organizationId],
            ['provider' => self::PROVIDER_PLATFORM, 'api_key' => null]
        );
    }

    public function hasKey(): bool
    {
        return in_array($this->provider, [self::PROVIDER_SERPER, self::PROVIDER_BRAVE], true)
            && ! empty($this->api_key);
    }

    public function usesPlatform(): bool
    {
        return $this->provider === self::PROVIDER_PLATFORM;
    }
}
