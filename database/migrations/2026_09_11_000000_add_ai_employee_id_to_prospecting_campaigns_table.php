<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prospecting_campaigns', function (Blueprint $table) {
            // Link a campaign to the AI Sales Employee that runs it (tenant-level).
            $table->foreignId('ai_employee_id')->nullable()->after('organization_id')
                ->constrained('ai_employees')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('prospecting_campaigns', function (Blueprint $table) {
            $table->dropConstrainedForeignId('ai_employee_id');
        });
    }
};
