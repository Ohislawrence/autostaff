<?php

namespace App\Jobs\Prospecting;

use App\Models\ProspectingCampaign;
use App\Services\Prospecting\ProspectHunterService;
use App\Services\Prospecting\ProspectQualifierService;
use App\Services\Prospecting\ProspectingPlanGate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RunHuntJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600;
    public int $tries = 1;

    public function __construct(protected int $campaignId) {}

    public function handle(ProspectHunterService $hunter, ProspectQualifierService $qualifier, ProspectingPlanGate $gate): void
    {
        $campaign = ProspectingCampaign::withTrashed()->find($this->campaignId);

        if (! $campaign) {
            return;
        }

        if (! $gate->isAllowed($campaign->organization_id)) {
            return;
        }

        $result = $hunter->hunt($campaign);

        // Immediately qualify the freshly-hunted prospects so they get a 1-10 score.
        $campaign->prospects()->where('status', 'new')->get()->each(function ($prospect) use ($qualifier) {
            try {
                $qualifier->qualify($prospect);
            } catch (\Throwable $e) {
                Log::warning('Auto-qualify failed in RunHuntJob', [
                    'prospect_id' => $prospect->id,
                    'error' => $e->getMessage(),
                ]);
            }
        });

        Log::info('Prospecting hunt completed', [
            'campaign_id' => $campaign->id,
            'created' => $result['created'],
            'sources' => $result['sources'],
        ]);
    }
}
