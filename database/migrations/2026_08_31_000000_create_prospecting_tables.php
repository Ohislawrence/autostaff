<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Prospecting campaigns: one campaign = one Ideal Customer Profile + outreach run config.
        Schema::create('prospecting_campaigns', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('icp')->nullable(); // industry, company_size, geography, job_titles, keywords, exclusions, budget, pain_points
            $table->text('offer')->nullable(); // what we pitch (product/service + value prop + CTA)
            $table->string('tone')->default('professional');
            $table->string('sender_name')->nullable();
            $table->string('sender_email')->nullable();
            $table->integer('daily_limit')->default(25);
            $table->boolean('auto_outreach')->default(false);
            $table->string('status')->default('draft'); // draft, active, paused, completed
            $table->timestamp('last_run_at')->nullable();
            $table->timestamp('last_outreach_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // Prospects discovered / imported by the hunter.
        Schema::create('prospects', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('campaign_id')->constrained('prospecting_campaigns')->cascadeOnDelete();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('title')->nullable();
            $table->string('company')->nullable();
            $table->string('company_size')->nullable();
            $table->string('industry')->nullable();
            $table->string('location')->nullable();
            $table->string('website')->nullable();
            $table->string('linkedin_url')->nullable();
            $table->string('source')->default('manual'); // ai_generated, web_search, manual, csv
            $table->string('source_url')->nullable();
            $table->integer('score')->default(0); // 1-10
            $table->json('score_breakdown')->nullable();
            $table->text('qualification_notes')->nullable();
            $table->string('status')->default('new'); // new, qualified, disqualified, contacted, replied, converted, bounced, unsubscribed
            $table->string('email_subject')->nullable();
            $table->text('email_body')->nullable();
            $table->integer('pass')->default(0); // 0 = not generated, 1 = draft, 2 = finalized
            $table->timestamp('contacted_at')->nullable();
            $table->timestamp('replied_at')->nullable();
            $table->timestamp('last_reply_at')->nullable();
            $table->text('reply_summary')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['campaign_id', 'status']);
            $table->index(['campaign_id', 'score']);
            $table->unique(['campaign_id', 'email'], 'prospects_campaign_email_unique');
        });

        // Every outbound/inbound email tied to a prospect (audit trail of the 2-pass flow).
        Schema::create('outreach_messages', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('prospect_id')->constrained()->cascadeOnDelete();
            $table->foreignId('campaign_id')->constrained('prospecting_campaigns')->cascadeOnDelete();
            $table->string('direction')->default('outbound'); // outbound, inbound
            $table->integer('pass')->default(0); // 1 = AI draft, 2 = AI finalized/sent
            $table->string('subject')->nullable();
            $table->text('body')->nullable();
            $table->string('status')->default('draft'); // draft, sent, delivered, bounced, received
            $table->string('provider_message_id')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['prospect_id', 'created_at']);
            $table->index(['campaign_id', 'status']);
        });

        // Single-row platform settings (managed from the UI, no .env edits required).
        Schema::create('prospecting_settings', function (Blueprint $table) {
            $table->id();
            $table->string('telegram_bot_token')->nullable();
            $table->string('telegram_chat_id')->nullable();
            $table->string('alert_email')->nullable();
            $table->string('sender_name')->nullable();
            $table->string('sender_email')->nullable();
            $table->text('signature')->nullable();
            $table->string('deepseek_model')->nullable();
            $table->string('search_provider')->default('none'); // none, serper, brave
            $table->string('search_api_key')->nullable();
            $table->integer('daily_hunt_limit')->default(50);
            $table->integer('daily_outreach_limit')->default(50);
            $table->integer('qualification_threshold')->default(7);
            $table->boolean('auto_hunt')->default(false);
            $table->boolean('auto_outreach')->default(false);
            $table->string('reply_webhook_secret')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prospecting_settings');
        Schema::dropIfExists('outreach_messages');
        Schema::dropIfExists('prospects');
        Schema::dropIfExists('prospecting_campaigns');
    }
};
