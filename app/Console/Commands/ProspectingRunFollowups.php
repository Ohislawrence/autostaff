<?php

namespace App\Console\Commands;

use App\Jobs\Prospecting\ProspectFollowupJob;
use Illuminate\Console\Command;

class ProspectingRunFollowups extends Command
{
    protected $signature = 'prospecting:followups {--campaign=}';
    protected $description = 'Send due follow-up emails to prospects who have not replied.';

    public function handle(): int
    {
        $campaignId = $this->option('campaign') ? (int) $this->option('campaign') : null;

        ProspectFollowupJob::dispatch($campaignId);

        $this->info('Follow-up job dispatched.');

        return self::SUCCESS;
    }
}
