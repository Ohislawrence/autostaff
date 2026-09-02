<?php

namespace App\Http\Controllers;

use App\Models\ApiKey;
use App\Services\Api\ApiKeyService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class ApiKeyController extends Controller
{
    public const AVAILABLE_SCOPES = [
        'leads:read',
        'leads:create',
        'customers:read',
        'customers:create',
        'orders:read',
        'orders:create',
        'messages:send',
        'conversations:read',
        'ai-employees:read',
    ];

    public function __construct(protected ApiKeyService $keys) {}

    public function index()
    {
        $organization = current_org();

        $keys = $organization
            ? ApiKey::query()
                ->where('organization_id', $organization->id)
                ->latest()
                ->get()
                ->map(fn (ApiKey $key) => [
                    'id' => $key->id,
                    'name' => $key->name,
                    'key_prefix' => $key->key,
                    'scopes' => $key->scopes ?? [],
                    'is_active' => $key->is_active,
                    'last_used_at' => $key->last_used_at?->toISOString(),
                    'expires_at' => $key->expires_at?->toISOString(),
                    'revoked_at' => $key->revoked_at?->toISOString(),
                    'created_at' => $key->created_at->toISOString(),
                ])
            : collect();

        return Inertia::render('Settings/ApiKeys', [
            'keys' => $keys,
            'availableScopes' => self::AVAILABLE_SCOPES,
        ]);
    }

    public function store(Request $request)
    {
        $organization = current_org();
        abort_if(! $organization, 403, 'No active organization.');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'scopes' => ['nullable', 'array'],
            'scopes.*' => ['string', Rule::in(array_merge(['*'], self::AVAILABLE_SCOPES))],
        ]);

        $result = $this->keys->issue($organization, $validated['name'], $validated['scopes'] ?? []);

        return back()
            ->with('success', 'API key created. Copy it now — it will not be shown again.')
            ->with('api_key', $result['key']);
    }

    public function revoke(ApiKey $apiKey)
    {
        $this->authorizeKeyOwnership($apiKey);
        $this->keys->revoke($apiKey);

        return back()->with('success', "API key \"{$apiKey->name}\" revoked.");
    }

    public function regenerate(ApiKey $apiKey)
    {
        $this->authorizeKeyOwnership($apiKey);
        $newKey = $this->keys->regenerate($apiKey);

        return back()
            ->with('success', "API key \"{$apiKey->name}\" regenerated. Copy it now — it will not be shown again.")
            ->with('api_key', $newKey);
    }

    protected function authorizeKeyOwnership(ApiKey $apiKey): void
    {
        $organization = current_org();

        abort_if(
            ! $organization || $apiKey->organization_id !== $organization->id,
            403,
            'This API key does not belong to your organization.'
        );
    }
}
