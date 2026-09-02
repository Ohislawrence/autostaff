<?php

namespace App\Console\Commands;

use App\Models\Organization;
use App\Services\ReportingService;
use Illuminate\Console\Command;

class GenerateScheduledReports extends Command
{
    protected $signature = 'reports:generate {period=weekly}';
    protected $description = 'Generate business performance reports for all active organizations';

    public function handle(ReportingService $reporting): int
    {
        $period = $this->argument('period');
        $count = 0;

        Organization::where('is_active', true)
            ->where('onboarding_completed', true)
            ->chunk(50, function ($organizations) use ($reporting, $period, &$count) {
                foreach ($organizations as $organization) {
                    try {
                        $reporting->generate($organization->id, $period);
                        $count++;
                    } catch (\Throwable $e) {
                        $this->error("Failed for org {$organization->id}: {$e->getMessage()}");
                    }
                }
            });

        $this->info("Generated {$count} {$period} report(s).");

        return self::SUCCESS;
    }
}