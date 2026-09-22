<?php

namespace App\Jobs\Prospecting;

use App\Models\ProspectingCampaign;
use App\Services\Prospecting\ProspectQualifierService;
use App\Services\Prospecting\ProspectingPlanGate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class QualifyCampaignJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600;
    public int $tries = 1;

    public function __construct(protected int $campaignId) {}

    public function handle(ProspectQualifierService $qualifier, ProspectingPlanGate $gate): void
    {
        $campaign = ProspectingCampaign::withTrashed()->find($this->campaignId);

        if (! $campaign) {
            return;
        }

        if (! $gate->isAllowed($campaign->organization_id)) {
            return;
        }

        $count = 0;
        $campaign->prospects()->where('status', 'new')->get()->each(function ($prospect) use ($qualifier, &$count) {
            try {
                $qualifier->qualify($prospect);
                $count++;
            } catch (\Throwable $e) {
                Log::warning('Qualify failed in QualifyCampaignJob', [
                    'prospect_id' => $prospect->id,
                    'error' => $e->getMessage(),
                ]);
            }
        });

        Log::info('Prospecting qualification completed', [
            'campaign_id' => $campaign->id,
            'qualified' => $count,
        ]);
    }
}
