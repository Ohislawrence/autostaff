<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Appointments
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->uuid('uuid')->unique();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('ai_employee_id')->nullable()->constrained('ai_employees')->nullOnDelete();
            $table->foreignId('conversation_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('service')->nullable();
            $table->dateTime('start_time');
            $table->dateTime('end_time');
            $table->string('timezone')->default('UTC');
            $table->string('status')->default('scheduled'); // scheduled, confirmed, cancelled, completed, no_show
            $table->string('location')->nullable();
            $table->string('meeting_link')->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->boolean('reminder_sent')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['organization_id', 'start_time']);
            $table->index(['organization_id', 'customer_id']);
            $table->index(['organization_id', 'status']);
        });

        // Availability / Calendar
        Schema::create('availabilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('day_of_week'); // monday, tuesday, etc.
            $table->time('start_time');
            $table->time('end_time');
            $table->integer('slot_duration_minutes')->default(30);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Services (for appointments)
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('duration_minutes')->default(30);
            $table->decimal('price', 10, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Tasks
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->uuid('uuid')->unique();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('status')->default('open'); // open, in_progress, completed, cancelled
            $table->string('priority')->default('normal'); // low, normal, high, urgent
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('conversation_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('lead_id')->nullable()->constrained()->nullOnDelete();
            $table->dateTime('due_date')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['organization_id', 'status']);
            $table->index(['organization_id', 'assigned_to']);
        });

        // Automations
        Schema::create('automations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('trigger_type'); // new_lead, new_message, conversation_closed, order_created, payment_received, appointment_created, scheduled_time, customer_inactive
            $table->json('trigger_config')->nullable();
            $table->json('conditions')->nullable();
            $table->json('actions'); // actions to perform
            $table->boolean('is_active')->default(true);
            $table->integer('max_executions_per_day')->nullable();
            $table->integer('execution_count_today')->default(0);
            $table->timestamp('last_executed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // Automation Runs
        Schema::create('automation_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('automation_id')->constrained()->cascadeOnDelete();
            $table->string('execution_id')->unique(); // for loop protection
            $table->string('status'); // pending, running, completed, failed, aborted
            $table->json('trigger_data')->nullable();
            $table->json('condition_results')->nullable();
            $table->json('action_results')->nullable();
            $table->text('error_message')->nullable();
            $table->integer('workflow_depth')->default(0);
            $table->timestamps();

            $table->index('execution_id');
        });

        // Integrations
        Schema::create('integrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->uuid('uuid')->unique();
            $table->string('provider'); // whatsapp, woocommerce, shopify, google_calendar, etc.
            $table->string('name');
            $table->boolean('is_connected')->default(false);
            $table->json('config')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->string('status')->default('inactive');
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        // Integration Credentials (encrypted)
        Schema::create('integration_credentials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('integration_id')->constrained()->cascadeOnDelete();
            $table->string('key'); // api_key, access_token, webhook_secret, etc.
            $table->text('value'); // encrypted
            $table->string('type')->default('encrypted');
            $table->timestamps();

            $table->unique(['integration_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_credentials');
        Schema::dropIfExists('integrations');
        Schema::dropIfExists('automation_runs');
        Schema::dropIfExists('automations');
        Schema::dropIfExists('tasks');
        Schema::dropIfExists('services');
        Schema::dropIfExists('availabilities');
        Schema::dropIfExists('appointments');
    }
};