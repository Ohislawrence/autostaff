<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('knowledge_gaps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->uuid('uuid')->unique();
            $table->string('category'); // escalation, failed_tool, low_confidence, unanswered, feedback
            $table->string('source')->default('automatic');
            $table->text('question');
            $table->integer('frequency')->default(1);
            $table->string('status')->default('open'); // open, resolved, ignored
            $table->text('suggested_answer')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'status']);
            $table->index(['organization_id', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('knowledge_gaps');
    }
};