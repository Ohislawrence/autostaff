<?php

namespace App\Console\Commands;

use App\Services\Platform\GrowthService;
use Illuminate\Console\Command;

class GrowthEvaluate extends Command
{
    protected $signature = 'growth:evaluate';

    protected $description = 'Evaluate achievements and auto-log marketing metrics for the platform owner';

    public function handle(GrowthService $growth): int
    {
        $growth->evaluateAchievements();
        $logged = $growth->autoLogMetrics();

        $this->info("Achievements evaluated and {$logged} marketing metrics auto-logged.");

        return self::SUCCESS;
    }
}
