<?php

namespace Database\Seeders;

use App\Ai\Tools\ToolRegistry;
use Illuminate\Database\Seeder;

class ToolsTableSeeder extends Seeder
{
    /**
     * Sync all AI tools registered in the application to the database.
     *
     * The ToolRegistry is the single source of truth for built-in tools.
     * This ensures the tools table always matches the codebase (and picks up
     * new tools without manual seeder edits), while preserving any custom
     * tenant tools that may share identifiers.
     */
    public function run(): void
    {
        app(ToolRegistry::class)->syncToDatabase();
    }
}