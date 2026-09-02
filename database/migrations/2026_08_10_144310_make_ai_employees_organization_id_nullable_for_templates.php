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
        Schema::table('ai_employees', function (Blueprint $table) {
            // Drop existing foreign key constraint
            $table->dropForeign(['organization_id']);
            
            // Make organization_id nullable (for templates)
            $table->foreignId('organization_id')->nullable()->change();
            
            // Re-add foreign key with nullable support
            $table->foreign('organization_id')
                ->references('id')
                ->on('organizations')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ai_employees', function (Blueprint $table) {
            // Drop foreign key
            $table->dropForeign(['organization_id']);
            
            // Make organization_id required again
            $table->foreignId('organization_id')->nullable(false)->change();
            
            // Re-add foreign key
            $table->foreign('organization_id')
                ->references('id')
                ->on('organizations')
                ->cascadeOnDelete();
        });
    }
};
