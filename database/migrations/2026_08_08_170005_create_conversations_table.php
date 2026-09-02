<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->uuid('uuid')->unique();
            $table->foreignId('ai_employee_id')->nullable()->constrained('ai_employees')->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('assigned_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('channel'); // website_chat, whatsapp, email, api
            $table->string('channel_conversation_id')->nullable(); // external ID
            $table->string('subject')->nullable();
            $table->string('status')->default('open'); // open, ai_handling, waiting_customer, human_required, assigned, resolved, closed
            $table->string('priority')->default('normal'); // low, normal, high, urgent
            $table->text('ai_summary')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('last_message_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['organization_id', 'status']);
            $table->index(['organization_id', 'ai_employee_id']);
            $table->index(['organization_id', 'customer_id']);
            $table->index('last_message_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};