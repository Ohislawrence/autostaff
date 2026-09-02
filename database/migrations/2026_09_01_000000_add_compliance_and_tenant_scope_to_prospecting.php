<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tenant scoping + compliance for prospecting campaigns.
        Schema::table('prospecting_campaigns', function (Blueprint $table) {
            $table->foreignId('organization_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->text('postal_address')->nullable();
            $table->string('from_domain')->nullable();
            $table->json('compliance_regions')->nullable(); // e.g. ['us','uk']
            $table->json('sourcing_rules')->nullable();
            $table->json('send_window')->nullable(); // {tz, start, end, days[]}
            $table->integer('max_per_hour')->default(50);
            $table->boolean('require_approval_ai_contacts')->default(false);
            $table->index('organization_id');
        });

        // Compliance + provenance on prospects.
        Schema::table('prospects', function (Blueprint $table) {
            $table->foreignId('organization_id')->nullable()->after('campaign_id')->constrained()->nullOnDelete();
            $table->string('data_subject_type')->default('unknown')->after('source'); // corporate, individual, sole_trader, unknown
            $table->string('legal_basis')->nullable()->after('data_subject_type'); // legitimate_interest, consent
            $table->string('consent_status')->default('none')->after('legal_basis');
            $table->string('validation_status')->default('pending')->after('consent_status'); // pending, valid, risky, invalid, role, disposable
            $table->json('validation_details')->nullable()->after('validation_status');
            $table->timestamp('validated_at')->nullable()->after('validation_details');
            $table->json('provenance')->nullable()->after('source_url');
            $table->timestamp('discovered_at')->nullable()->after('provenance');
            $table->string('unsubscribe_token')->nullable()->unique()->after('reply_summary');
            $table->timestamp('suppressed_at')->nullable()->after('unsubscribe_token');
            $table->string('suppression_reason')->nullable()->after('suppressed_at');

            $table->index('organization_id');
            $table->index(['organization_id', 'validation_status']);
        });

        // Compliance + delivery tracking on outreach messages.
        Schema::table('outreach_messages', function (Blueprint $table) {
            $table->foreignId('organization_id')->nullable()->after('campaign_id')->constrained()->nullOnDelete();
            $table->string('compliance_status')->default('pending')->after('status'); // pending, approved, blocked, needs_review
            $table->json('compliance_checks')->nullable()->after('compliance_status');
            $table->json('message_headers')->nullable()->after('compliance_checks');
            $table->timestamp('bounced_at')->nullable()->after('delivered_at');
            $table->timestamp('complained_at')->nullable()->after('bounced_at');
            $table->index('organization_id');
        });

        // Global/tenant/campaign do-not-contact suppression list.
        Schema::create('suppression_list', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('email');
            $table->string('email_hash')->unique();
            $table->string('reason'); // unsubscribe, bounce, complaint, dnc, invalid
            $table->string('source')->default('manual');
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('campaign_id')->nullable()->constrained('prospecting_campaigns')->nullOnDelete();
            $table->timestamps();

            $table->index('email');
            $table->index('organization_id');
        });

        // Immutable audit/provenance trail for every prospect action.
        Schema::create('prospect_events', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('prospect_id')->constrained()->cascadeOnDelete();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type'); // discovered, validated, suppressed, sent, bounced, complained, unsubscribed, source_rule_blocked
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->index(['prospect_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prospect_events');
        Schema::dropIfExists('suppression_list');

        Schema::table('outreach_messages', function (Blueprint $table) {
            $table->dropColumn(['organization_id', 'compliance_status', 'compliance_checks', 'message_headers', 'bounced_at', 'complained_at']);
        });

        Schema::table('prospects', function (Blueprint $table) {
            $table->dropColumn([
                'organization_id', 'data_subject_type', 'legal_basis', 'consent_status',
                'validation_status', 'validation_details', 'validated_at', 'provenance',
                'discovered_at', 'unsubscribe_token', 'suppressed_at', 'suppression_reason',
            ]);
        });

        Schema::table('prospecting_campaigns', function (Blueprint $table) {
            $table->dropColumn(['organization_id', 'postal_address', 'from_domain', 'compliance_regions', 'sourcing_rules', 'send_window', 'max_per_hour', 'require_approval_ai_contacts']);
        });
    }
};
