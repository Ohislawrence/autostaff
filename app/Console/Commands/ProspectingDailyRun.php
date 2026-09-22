<?php

namespace App\Console\Commands;

use App\Jobs\Prospecting\RunHuntJob;
use App\Jobs\Prospecting\RunOutreachJob;
use App\Models\ProspectingCampaign;
use App\Models\ProspectingSettings;
use Illuminate\Console\Command;

class ProspectingDailyRun extends Command
{
    protected $signature = 'prospecting:daily-run {--force : Run even when auto-hunt is disabled}';
    protected $description = 'Run the automated Hunt → Qualify → Outreach pipeline for active campaigns';

    public function handle(): int
    {
        $settings = ProspectingSettings::instance();

        if (! $settings->auto_hunt && ! $this->option('force')) {
            $this->info('Auto-hunt is disabled. Re-run with --force to ignore this.');

            return self::SUCCESS;
        }

        $campaigns = ProspectingCampaign::where('status', 'active')->get();

        if ($campaigns->isEmpty()) {
            $this->info('No active campaigns to run.');

            return self::SUCCESS;
        }

        foreach ($campaigns as $campaign) {
            // Skip campaigns already hunted today (e.g. by the hourly tenant runner).
            if ($campaign->last_run_at && $campaign->last_run_at->isToday()) {
                $this->line("Skipping <info>{$campaign->name}</info> — already ran today.");
                continue;
            }

            if ($campaign->auto_outreach) {
                RunHuntJob::withChain([new RunOutreachJob($campaign->id)])->dispatch($campaign->id);
            } else {
                RunHuntJob::dispatch($campaign->id);
            }

            $this->line("Dispatched pipeline for <info>{$campaign->name}</info>");
        }

        return self::SUCCESS;
    }
}
