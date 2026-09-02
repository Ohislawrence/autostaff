<?php

namespace App\Services\Api;

use App\Models\ApiKey;
use App\Models\Organization;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ApiKeyService
{
    /**
     * Create a new API key and return the plain-text key (shown only once).
     *
     * @return array{key: string, apiKey: ApiKey}
     */
    public function issue(Organization $organization, string $name, array $scopes = [], ?\DateTimeInterface $expiresAt = null, ?int $pluginInstallationId = null): array
    {
        [$lookup, $fullKey, $hash] = $this->generate();

        $apiKey = ApiKey::create([
            'organization_id' => $organization->id,
            'name' => $name,
            'key' => $lookup,
            'hashed_key' => $hash,
            'scopes' => $scopes,
            'expires_at' => $expiresAt,
            'is_active' => true,
            'plugin_installation_id' => $pluginInstallationId,
        ]);

        audit_log('api_key_created', "API key \"{$name}\" created", ['name' => $name, 'scopes' => $scopes]);

        return ['key' => $fullKey, 'apiKey' => $apiKey];
    }

    /**
     * Rotate the secret for an existing key and return the new plain-text key.
     */
    public function regenerate(ApiKey $apiKey): string
    {
        [$lookup, $fullKey, $hash] = $this->generate();

        $apiKey->forceFill([
            'key' => $lookup,
            'hashed_key' => $hash,
            'is_active' => true,
            'revoked_at' => null,
        ])->save();

        audit_log('api_key_regenerated', "API key \"{$apiKey->name}\" regenerated");

        return $fullKey;
    }

    /**
     * Immediately revoke a key.
     */
    public function revoke(ApiKey $apiKey): void
    {
        $apiKey->forceFill([
            'is_active' => false,
            'revoked_at' => now(),
        ])->save();

        audit_log('api_key_revoked', "API key \"{$apiKey->name}\" revoked");
    }

    /**
     * Generate a lookup prefix, full secret, and its bcrypt hash.
     *
     * The full key is "{lookup}.{secret}"; only the lookup prefix and the
     * hash are persisted, so the raw secret can never be recovered from the DB.
     *
     * @return array{0: string, 1: string, 2: string} [lookup, fullKey, hash]
     */
    protected function generate(): array
    {
        $lookup = 'ak_'.Str::random(16);
        $secret = Str::random(40);
        $fullKey = $lookup.'.'.$secret;
        $hash = Hash::make($fullKey);

        return [$lookup, $fullKey, $hash];
    }
}
