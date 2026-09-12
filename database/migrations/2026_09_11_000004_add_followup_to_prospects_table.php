<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prospects', function (Blueprint $table) {
            $table->unsignedInteger('followup_count')->default(0)->after('pass');
            $table->timestamp('last_followup_at')->nullable()->after('followup_count');
        });
    }

    public function down(): void
    {
        Schema::table('prospects', function (Blueprint $table) {
            $table->dropColumn(['followup_count', 'last_followup_at']);
        });
    }
};
