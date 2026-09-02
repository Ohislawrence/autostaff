<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            if (! Schema::hasColumn('customers', 'ai_summary')) {
                $table->json('ai_summary')->nullable()->after('last_contacted_at');
            }
            if (! Schema::hasColumn('customers', 'language_preference')) {
                $table->string('language_preference')->nullable()->after('ai_summary');
            }
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['ai_summary', 'language_preference']);
        });
    }
};