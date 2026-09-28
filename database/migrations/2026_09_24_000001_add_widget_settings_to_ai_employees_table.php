<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_employees', function (Blueprint $table) {
            $table->json('widget_settings')->nullable()->after('response_settings');
        });
    }

    public function down(): void
    {
        Schema::table('ai_employees', function (Blueprint $table) {
            $table->dropColumn('widget_settings');
        });
    }
};
