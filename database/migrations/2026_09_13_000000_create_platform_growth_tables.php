<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Marketing channels = one acquisition motion (content, SEO, ads, partner, outbound, referral...)
        Schema::create('marketing_channels', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->string('type')->default('other'); // content, seo, ads, partner, outbound, referral, social, email, other
            $table->text('goal')->nullable();
            $table->decimal('budget', 15, 2)->default(0);
            $table->string('status')->default('active'); // active, paused, completed
            $table->timestamp('start_at')->nullable();
            $table->timestamp('end_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // Time-series metrics per channel (impressions, clicks, spend, leads, signups, mrr).
        Schema::create('marketing_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('channel_id')->constrained('marketing_channels')->cascadeOnDelete();
            $table->string('metric'); // impressions, clicks, spend, leads, signups, mrr
            $table->decimal('value', 15, 2)->default(0);
            $table->date('recorded_on');
            $table->timestamps();

            $table->index(['channel_id', 'metric', 'recorded_on']);
        });

        // Personal daily tasks for the platform owner (distinct from tenant `tasks`).
        Schema::create('platform_tasks', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('category')->default('ops'); // marketing, ops, support, ai, other
            $table->string('priority')->default('normal'); // low, normal, high, urgent
            $table->string('status')->default('open'); // open, completed
            $table->string('recurrence')->default('none'); // none, daily, weekdays, weekly
            $table->timestamp('due_date')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->integer('streak_count')->default(0);
            $table->timestamps();
        });

        // Completion log per task/day, used to compute streaks reliably.
        Schema::create('platform_task_completions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('platform_task_id')->constrained('platform_tasks')->cascadeOnDelete();
            $table->date('completed_on');
            $table->timestamps();

            $table->unique(['platform_task_id', 'completed_on']);
        });

        // Growth goals tied to live platform metrics (MRR, customers, activation, churn...).
        Schema::create('platform_goals', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('title');
            $table->string('metric_key');
            $table->decimal('target', 15, 2)->default(0);
            $table->string('unit')->nullable(); // currency, count, percent
            $table->string('period')->default('month'); // month, quarter, year, custom
            $table->timestamp('start_at')->nullable();
            $table->timestamp('end_at')->nullable();
            $table->string('color')->default('text-blue-600');
            $table->string('status')->default('active'); // active, archived
            $table->timestamps();
        });

        // Achievement definitions (seeded), evaluated against live metrics.
        Schema::create('achievements', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('icon')->default('🏆');
            $table->integer('points')->default(0);
            $table->string('condition_key'); // metric key used to evaluate progress
            $table->decimal('target', 15, 2)->default(1);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // Per-user achievement progress + unlock timestamps.
        Schema::create('user_achievements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('achievement_id')->constrained()->cascadeOnDelete();
            $table->decimal('progress', 15, 2)->default(0);
            $table->decimal('target', 15, 2)->default(0);
            $table->timestamp('unlocked_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'achievement_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_achievements');
        Schema::dropIfExists('achievements');
        Schema::dropIfExists('platform_goals');
        Schema::dropIfExists('platform_task_completions');
        Schema::dropIfExists('platform_tasks');
        Schema::dropIfExists('marketing_metrics');
        Schema::dropIfExists('marketing_channels');
    }
};
