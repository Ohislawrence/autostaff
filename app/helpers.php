<?php

use App\Models\Organization;
use App\Support\Currency;

/**
 * Get the current organization safely.
 * Returns null if no tenant context exists (Platform Owner, etc.).
 */
function current_org(): ?Organization
{
    // Try container binding first (set by ResolveTenant middleware)
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
 * Get the current organization ID.
 */
function current_org_id(): ?int
{
    return current_org()?->id;
}

/**
 * Get the API key that authenticated the current request (if any).
 */
function current_api_key(): ?\App\Models\ApiKey
{
    return app()->bound('current_api_key') ? app('current_api_key') : null;
}

/**
 * Get the plugin installation associated with the current API key (if any).
 */
function current_plugin_installation(): ?\App\Models\PluginInstallation
{
    return current_api_key()?->pluginInstallation;
}

/**
 * Write an audit log entry without ever breaking the request.
 */
function audit_log(string $event, string $description, array $metadata = []): void
{
    try {
        \App\Models\AuditLog::create([
            'organization_id' => current_org_id(),
            'user_id' => auth()->id(),
            'event' => $event,
            'description' => $description,
            'new_values' => $metadata,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'metadata' => $metadata,
        ]);
    } catch (\Throwable $e) {
        // Never let audit logging break a request.
    }
}

/**
 * Get the current organization's currency code (ISO-4217).
 * Falls back to USD when no tenant context exists.
 */
function tenant_currency(): string
{
    return Currency::normalize(current_org()?->currency);
}

/**
 * Format a monetary amount using the current organization's currency.
 */
function format_money(float|int|string $amount, ?string $currency = null): string
{
    return Currency::format($amount, $currency ?? tenant_currency());
}
