<?php

namespace App\Console\Commands;

use App\Jobs\Prospecting\RunHuntJob;
use App\Jobs\Prospecting\RunOutreachJob;
use App\Models\ProspectingCampaign;
use App\Models\ProspectingSettings;
use Illuminate\Console\Command;

class ProspectingRunTenants extends Command
{
    protected $signature = 'prospecting:run-tenants {--organization=}';
    protected $description = 'Run active outbound campaigns for tenant SDR employees.';

    public function handle(): int
    {
        $settings = ProspectingSettings::instance();

        if (! $settings->auto_hunt) {
            $this->info('Auto-hunt is disabled.');

            return self::SUCCESS;
        }

        $query = ProspectingCampaign::whereNotNull('organization_id')->where('status', 'active');

        if ($org = $this->option('organization')) {
            $query->where('organization_id', $org);
        }

        // Only run campaigns that have not already been hunted today, so an
        // active tenant cannot trigger AI hunts every hour.
        $query->where(function ($q) {
            $q->whereNull('last_run_at')
              ->orWhereDate('last_run_at', '!=', now()->toDateString());
        });

        $campaigns = $query->get();

        if ($campaigns->isEmpty()) {
            $this->info('No active tenant campaigns to run.');

            return self::SUCCESS;
        }

        foreach ($campaigns as $campaign) {
            if ($campaign->auto_outreach) {
                RunHuntJob::withChain([new RunOutreachJob($campaign->id)])->dispatch($campaign->id);
            } else {
                RunHuntJob::dispatch($campaign->id);
            }

            $this->line("Dispatched tenant pipeline for <info>{$campaign->name}</info>");
        }

        return self::SUCCESS;
    }
}
