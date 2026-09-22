<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->integer('max_prospecting_campaigns')->nullable()->default(0)->after('max_storage_bytes');
            $table->integer('max_daily_prospects')->nullable()->default(0)->after('max_prospecting_campaigns');
            $table->integer('max_daily_outreach')->nullable()->default(0)->after('max_daily_prospects');
        });

        // Existing plans: Free/Starter have no prospecting; Business is limited;
        // Professional is more open; Enterprise is unlimited (null).
        DB::table('plans')->where('slug', 'business')->update([
            'max_prospecting_campaigns' => 2,
            'max_daily_prospects' => 25,
            'max_daily_outreach' => 50,
        ]);
        DB::table('plans')->where('slug', 'professional')->update([
            'max_prospecting_campaigns' => 5,
            'max_daily_prospects' => 100,
            'max_daily_outreach' => 200,
        ]);
        DB::table('plans')->where('slug', 'enterprise')->update([
            'max_prospecting_campaigns' => null,
            'max_daily_prospects' => null,
            'max_daily_outreach' => null,
        ]);
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn(['max_prospecting_campaigns', 'max_daily_prospects', 'max_daily_outreach']);
        });
    }
};
