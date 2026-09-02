<?php

namespace App\Services\Prospecting;

use App\Models\SuppressionList;
use Illuminate\Support\Collection;

class SuppressionService
{
    public function isSuppressed(?string $email): bool
    {
        if (! $email) {
            return false;
        }

        return SuppressionList::where('email_hash', $this->hash($email))->exists();
    }

    public function suppress(
        ?string $email,
        string $reason = 'dnc',
        ?int $organizationId = null,
        ?int $campaignId = null,
        string $source = 'manual',
    ): ?SuppressionList {
        $email = strtolower(trim((string) $email));
        if (! $email) {
            return null;
        }

        return SuppressionList::firstOrCreate(
            ['email_hash' => $this->hash($email)],
            [
                'email' => $email,
                'reason' => $reason,
                'source' => $source,
                'organization_id' => $organizationId,
                'campaign_id' => $campaignId,
            ]
        );
    }

    public function remove(?string $email): void
    {
        if (! $email) {
            return;
        }

        SuppressionList::where('email_hash', $this->hash($email))->delete();
    }

    public function all(): Collection
    {
        return SuppressionList::latest()->get();
    }

    protected function hash(string $email): string
    {
        return sha1(strtolower(trim($email)));
    }
}
