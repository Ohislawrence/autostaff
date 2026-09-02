<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Feature Flags
        Schema::create('platform_feature_flags', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('key')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_enabled')->default(false);
            $table->json('plan_availability')->nullable(); // null = all, ['enterprise'] = enterprise only
            $table->json('beta_organizations')->nullable(); // org IDs for beta
            $table->timestamps();
        });

        // Support Sessions (platform admin impersonation)
        Schema::create('support_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('reason');
            $table->string('status')->default('active'); // active, ended
            $table->integer('actions_count')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();
        });

        // Support Tickets
        Schema::create('support_tickets', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('assigned_admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('subject');
            $table->text('description');
            $table->string('status')->default('open'); // open, pending, escalated, resolved, closed
            $table->string('priority')->default('normal'); // low, normal, high, urgent
            $table->text('internal_notes')->nullable();
            $table->timestamps();
        });

        // Platform Announcements
        Schema::create('platform_announcements', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('body');
            $table->string('type')->default('info'); // info, warning, maintenance, success
            $table->json('target_plans')->nullable(); // null = all
            $table->json('target_organizations')->nullable();
            $table->boolean('send_email')->default(false);
            $table->boolean('send_in_app')->default(true);
            $table->timestamp('published_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_announcements');
        Schema::dropIfExists('support_tickets');
        Schema::dropIfExists('support_sessions');
        Schema::dropIfExists('platform_feature_flags');
    }
};