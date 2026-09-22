<?php

namespace App\Services\Prospecting;

use App\Models\Organization;
use App\Models\Plan;

/**
 * Central place for plan-gating the outbound Prospecting engine.
 *
 * Free and Starter plans have no access; Business is limited; Professional is
 * more open; Enterprise is unlimited.
 */
class ProspectingPlanGate
{
    public function plan(Organization|int|null $organization = null): ?Plan
    {
        return $this->resolve($organization)?->activePlan();
    }

    public function isAllowed(Organization|int|null $organization = null): bool
    {
        return (bool) $this->plan($organization)?->allowsProspecting();
    }

    /**
     * @return array{max_campaigns: ?int, max_daily_prospects: ?int, max_daily_outreach: ?int}
     */
    public function limits(Organization|int|null $organization = null): array
    {
        return $this->plan($organization)?->prospectingLimits() ?? [
            'max_campaigns' => 0,
            'max_daily_prospects' => 0,
            'max_daily_outreach' => 0,
        ];
    }

    public function maxCampaigns(Organization|int|null $organization = null): ?int
    {
        return $this->limits($organization)['max_campaigns'];
    }

    public function maxDailyProspects(Organization|int|null $organization = null): ?int
    {
        return $this->limits($organization)['max_daily_prospects'];
    }

    public function maxDailyOutreach(Organization|int|null $organization = null): ?int
    {
        return $this->limits($organization)['max_daily_outreach'];
    }

    protected function resolve(Organization|int|null $organization): ?Organization
    {
        if ($organization instanceof Organization) {
            return $organization;
        }

        if (is_int($organization)) {
            return Organization::find($organization);
        }

        return current_org();
    }
}
