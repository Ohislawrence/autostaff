<?php

namespace App\Console\Commands;

use App\Ai\Tools\ToolRegistry;
use Illuminate\Console\Command;

class SyncToolsCommand extends Command
{
    protected $signature = 'tools:sync';
    protected $description = 'Sync all registered AI tools to the database so they are available in the platform and tenant portals.';

    public function handle(ToolRegistry $registry): int
    {
        $registry->syncToDatabase();

        $count = count($registry->all());
        $this->info("Synced {$count} tools to the database.");

        return self::SUCCESS;
    }
}