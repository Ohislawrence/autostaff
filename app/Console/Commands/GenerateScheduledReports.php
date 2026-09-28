<?php

namespace App\Console\Commands;

use App\Mail\AiWorkSummary;
use App\Models\GeneratedReport;
use App\Models\Organization;
use App\Services\ReportingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

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
                        $report = $reporting->generate($organization->id, $period);
                        $this->sendAlertIfEnabled($organization, $report, $period);
                        $count++;
                    } catch (\Throwable $e) {
                        $this->error("Failed for org {$organization->id}: {$e->getMessage()}");
                    }
                }
            });

        $this->info("Generated {$count} {$period} report(s).");

        return self::SUCCESS;
    }

    protected function sendAlertIfEnabled(Organization $organization, GeneratedReport $report, string $period): void
    {
        $alerts = $organization->report_alerts ?? [];

        if (empty($alerts['enabled'])) {
            return;
        }

        if (($alerts['frequency'] ?? 'weekly') !== $period) {
            return;
        }

        $recipients = array_values(array_filter($alerts['recipients'] ?? []));
        if (empty($recipients)) {
            return;
        }

        try {
            Mail::to($recipients)->send(new AiWorkSummary($organization, $report));
            $this->info("Emailed {$period} summary to organization {$organization->id}.");
        } catch (\Throwable $e) {
            $this->error("Failed to email report for organization {$organization->id}: {$e->getMessage()}");
        }
    }
}