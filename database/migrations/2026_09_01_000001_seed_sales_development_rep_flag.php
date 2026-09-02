<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('platform_feature_flags')->insertOrIgnore([
            'name' => 'Sales Development Rep (Prospecting)',
            'key' => 'sales_development_rep',
            'is_enabled' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('platform_feature_flags')->where('key', 'sales_development_rep')->delete();
    }
};
