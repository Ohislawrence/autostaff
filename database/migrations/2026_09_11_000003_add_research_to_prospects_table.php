<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prospects', function (Blueprint $table) {
            $table->text('research_notes')->nullable()->after('qualification_notes');
            $table->json('research_sources')->nullable()->after('research_notes');
            $table->timestamp('researched_at')->nullable()->after('research_sources');
        });
    }

    public function down(): void
    {
        Schema::table('prospects', function (Blueprint $table) {
            $table->dropColumn(['research_notes', 'research_sources', 'researched_at']);
        });
    }
};
