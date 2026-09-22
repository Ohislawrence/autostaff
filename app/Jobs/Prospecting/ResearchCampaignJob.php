<?php

namespace App\Jobs\Prospecting;

use App\Models\ProspectingCampaign;
use App\Services\Prospecting\ProspectResearcherService;
use App\Services\Prospecting\ProspectingPlanGate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ResearchCampaignJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600;
    public int $tries = 1;

    public function __construct(protected int $campaignId) {}

    public function handle(ProspectResearcherService $researcher, ProspectingPlanGate $gate): void
    {
        $campaign = ProspectingCampaign::withTrashed()->find($this->campaignId);

        if (! $campaign) {
            return;
        }

        if (! $gate->isAllowed($campaign->organization_id)) {
            return;
        }

        $count = 0;
        $campaign->prospects()->where('status', 'qualified')->get()->each(function ($prospect) use ($researcher, &$count) {
            try {
                $researcher->research($prospect);
                $count++;
            } catch (\Throwable $e) {
                Log::warning('Prospect research failed in ResearchCampaignJob', [
                    'prospect_id' => $prospect->id,
                    'error' => $e->getMessage(),
                ]);
            }
        });

        Log::info('Prospecting research completed', [
            'campaign_id' => $campaign->id,
            'researched' => $count,
        ]);
    }
}
