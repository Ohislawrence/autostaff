<?php

namespace App\Jobs\Prospecting;

use App\Models\ProspectingCampaign;
use App\Models\ProspectingSettings;
use App\Services\Prospecting\OutreachService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RunOutreachJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600;
    public int $tries = 1;

    public function __construct(protected int $campaignId) {}

    public function handle(OutreachService $outreach): void
    {
        $campaign = ProspectingCampaign::withTrashed()->find($this->campaignId);

        if (! $campaign) {
            return;
        }

        $limit = max(1, (int) (ProspectingSettings::instance()->daily_outreach_limit ?: 50));

        $prospects = $campaign->prospects()
            ->where('status', 'qualified')
            ->whereNull('contacted_at')
            ->orderByDesc('score')
            ->limit($limit)
            ->get();

        $sent = 0;
        foreach ($prospects as $prospect) {
            try {
                $result = $outreach->send($prospect);
                if (! empty($result['sent'])) {
                    $sent++;
                }
            } catch (\Throwable $e) {
                Log::warning('Outreach send failed in RunOutreachJob', [
                    'prospect_id' => $prospect->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('Prospecting outreach completed', [
            'campaign_id' => $campaign->id,
            'sent' => $sent,
        ]);
    }
}
