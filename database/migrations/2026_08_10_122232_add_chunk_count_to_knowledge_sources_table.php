<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('knowledge_sources', function (Blueprint $table) {
            if (! Schema::hasColumn('knowledge_sources', 'chunk_count')) {
                $table->integer('chunk_count')->default(0)->after('error_message');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('knowledge_sources', function (Blueprint $table) {
            if (Schema::hasColumn('knowledge_sources', 'chunk_count')) {
                $table->dropColumn('chunk_count');
            }
        });
    }
};