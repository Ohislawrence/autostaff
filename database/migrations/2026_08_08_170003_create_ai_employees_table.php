<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->string('role'); // sales, support, receptionist, etc.
            $table->text('description')->nullable();
            $table->string('avatar')->nullable();
            $table->text('system_instructions')->nullable();
            $table->string('personality')->nullable();
            $table->string('tone')->nullable(); // professional, friendly, casual
            $table->string('language')->default('en');
            $table->json('business_knowledge_ids')->nullable(); // linked knowledge bases
            $table->json('enabled_tools')->nullable();
            $table->json('allowed_channels')->nullable();
            $table->json('working_hours')->nullable();
            $table->json('escalation_rules')->nullable();
            $table->json('response_settings')->nullable();
            $table->string('ai_model')->default('deepseek-chat');
            $table->decimal('temperature', 3, 2)->default(0.7);
            $table->integer('max_tool_calls')->default(5);
            $table->integer('max_context_messages')->default(20);
            $table->boolean('is_active')->default(false);
            $table->boolean('is_template')->default(false);
            $table->string('template_type')->nullable();
            $table->integer('conversations_count')->default(0);
            $table->integer('leads_generated')->default(0);
            $table->integer('escalation_rate')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['organization_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_employees');
    }
};