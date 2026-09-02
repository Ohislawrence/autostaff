<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Billing Plans
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2)->default(0);
            $table->string('currency')->default('USD');
            $table->string('billing_period')->default('monthly'); // monthly, yearly
            $table->integer('max_ai_employees')->default(1);
            $table->integer('max_messages_per_month')->default(1000);
            $table->integer('max_tool_calls_per_month')->default(500);
            $table->integer('max_knowledge_sources')->default(5);
            $table->bigInteger('max_storage_bytes')->nullable();
            $table->json('features')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // Subscriptions
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->uuid('uuid')->unique();
            $table->foreignId('plan_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('trial'); // trial, active, past_due, cancelled, expired
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('provider')->nullable(); // paystack, paddle, etc.
            $table->string('provider_subscription_id')->nullable();
            $table->json('provider_data')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'status']);
        });

        // Usage Records
        Schema::create('usage_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('type'); // messages, tokens, tool_calls, knowledge_processing, storage
            $table->bigInteger('quantity')->default(0);
            $table->json('metadata')->nullable();
            $table->date('recorded_at');
            $table->timestamps();

            $table->index(['organization_id', 'type', 'recorded_at']);
        });

        // API Keys
        Schema::create('api_keys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->string('key')->unique();
            $table->string('hashed_key')->unique();
            $table->json('permissions')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Webhooks
        Schema::create('webhooks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->string('url');
            $table->json('events'); // conversation.created, lead.created, etc.
            $table->string('secret')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('max_retries')->default(3);
            $table->timestamps();
        });

        // Webhook Deliveries
        Schema::create('webhook_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('webhook_id')->constrained()->cascadeOnDelete();
            $table->string('event');
            $table->json('payload');
            $table->integer('attempt')->default(1);
            $table->string('status')->default('pending'); // pending, success, failed, retrying
            $table->integer('response_code')->nullable();
            $table->text('response_body')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('next_retry_at')->nullable();
            $table->timestamps();

            $table->index(['webhook_id', 'status']);
            $table->index('next_retry_at');
        });

        // Notifications
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('type'); // in_app, email, webhook, push
            $table->morphs('notifiable');
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('body');
            $table->json('data')->nullable();
            $table->string('channel')->nullable(); // notification channel used
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'created_at']);
        });

        // Audit Logs
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('organization_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event'); // user_login, ai_employee_updated, tool_executed, etc.
            $table->string('auditable_type')->nullable();
            $table->unsignedBigInteger('auditable_id')->nullable();
            $table->text('description');
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'event']);
            $table->index(['auditable_type', 'auditable_id']);
            $table->index('created_at');
        });

        // AI Runs (observability)
        Schema::create('ai_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->uuid('uuid')->unique();
            $table->foreignId('ai_employee_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('conversation_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('message_id')->nullable()->constrained()->nullOnDelete();
            $table->string('provider')->default('deepseek');
            $table->string('model')->nullable();
            $table->integer('input_tokens')->default(0);
            $table->integer('output_tokens')->default(0);
            $table->integer('latency_ms')->nullable();
            $table->json('tools_called')->nullable();
            $table->json('knowledge_retrieved')->nullable();
            $table->text('system_prompt')->nullable();
            $table->text('user_prompt')->nullable();
            $table->text('assistant_response')->nullable();
            $table->decimal('estimated_cost', 10, 6)->default(0);
            $table->string('status'); // success, failed, error
            $table->text('error_message')->nullable();
            $table->string('correlation_id')->nullable(); // for tracing entire pipeline
            $table->integer('retry_count')->default(0);
            $table->timestamps();

            $table->index(['organization_id', 'created_at']);
            $table->index(['conversation_id', 'created_at']);
            $table->index('correlation_id');
        });

        // AI Evaluations
        Schema::create('ai_evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->uuid('uuid')->unique();
            $table->foreignId('ai_employee_id')->nullable()->constrained()->nullOnDelete();
            $table->string('category'); // correctness, knowledge_retrieval, tool_selection, policy_compliance, escalation, hallucination
            $table->text('input');
            $table->text('expected_output')->nullable();
            $table->text('actual_output')->nullable();
            $table->string('status')->default('pending'); // pending, passed, failed, needs_review
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        // Message Attachments
        Schema::create('message_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('message_id')->constrained()->cascadeOnDelete();
            $table->string('file_name');
            $table->string('file_path');
            $table->string('mime_type');
            $table->bigInteger('file_size')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        // Conversation Participants
        Schema::create('conversation_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->morphs('participant');
            $table->timestamp('joined_at')->nullable();
            $table->timestamp('left_at')->nullable();
            $table->timestamps();

            $table->unique(['conversation_id', 'participant_type', 'participant_id'], 'conv_participants_unique');
        });

        // Conversation Tags
        Schema::create('conversation_tags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->string('tag');
            $table->timestamps();

            $table->unique(['conversation_id', 'tag']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversation_tags');
        Schema::dropIfExists('conversation_participants');
        Schema::dropIfExists('message_attachments');
        Schema::dropIfExists('ai_evaluations');
        Schema::dropIfExists('ai_runs');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('webhook_deliveries');
        Schema::dropIfExists('webhooks');
        Schema::dropIfExists('api_keys');
        Schema::dropIfExists('usage_records');
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('plans');
    }
};