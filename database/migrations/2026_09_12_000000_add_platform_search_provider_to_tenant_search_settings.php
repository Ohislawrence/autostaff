<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // `platform` is now the default and explicit "use shared search" provider.
        Schema::table('tenant_search_settings', function (Blueprint $table) {
            $table->string('provider', 30)->default('platform')->change();
        });

        // Previously "none" silently fell back to the platform key; make that explicit.
        DB::table('tenant_search_settings')
            ->where('provider', 'none')
            ->update(['provider' => 'platform']);
    }

    public function down(): void
    {
        DB::table('tenant_search_settings')
            ->where('provider', 'platform')
            ->update(['provider' => 'none']);

        Schema::table('tenant_search_settings', function (Blueprint $table) {
            $table->string('provider', 30)->default('none')->change();
        });
    }
};
