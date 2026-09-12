<?php

namespace App\Services\Prospecting;

use App\Models\ProspectingCampaign;
use App\Models\Prospect;

/**
 * Sends follow-up emails to contacted prospects who haven't replied yet,
 * on a cadence (day 3 / 7 / 14 after the last touch).
 */
class ProspectFollowupService
{
    /** Follow-up cadence in days after contact / last follow-up. */
    protected const CADENCE_DAYS = [3, 7, 14];

    public function __construct(protected OutreachService $outreach) {}

    public function run(?int $campaignId = null): array
    {
        $sent = 0;

        $campaigns = ProspectingCampaign::query()
            ->where('status', 'active')
            ->when($campaignId, fn ($q) => $q->where('id', $campaignId))
            ->get();

        foreach ($campaigns as $campaign) {
            $sent += $this->runCampaign($campaign);
        }

        return ['sent' => $sent];
    }

    protected function runCampaign(ProspectingCampaign $campaign): int
    {
        $sent = 0;

        $prospects = $campaign->prospects()
            ->whereNotNull('contacted_at')
            ->whereNull('replied_at')
            ->where('status', '!=', 'unsubscribed')
            ->get();

        foreach ($prospects as $prospect) {
            if (! $this->isDue($prospect)) {
                continue;
            }

            $result = $this->outreach->sendFollowup($prospect);
            if (! empty($result['sent'])) {
                $sent++;
            }
        }

        return $sent;
    }

    protected function isDue(Prospect $prospect): bool
    {
        $index = (int) $prospect->followup_count;
        if ($index >= count(self::CADENCE_DAYS)) {
            return false;
        }

        $base = $prospect->last_followup_at ?: $prospect->contacted_at;
        if (! $base) {
            return false;
        }

        $days = self::CADENCE_DAYS[$index];

        return $base->copy()->addDays($days)->isPast();
    }
}
