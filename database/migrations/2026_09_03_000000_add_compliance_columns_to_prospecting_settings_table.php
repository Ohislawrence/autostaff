<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prospecting_settings', function (Blueprint $table) {
            $table->text('postal_address')->nullable()->after('reply_webhook_secret');
            $table->string('support_email')->nullable()->after('postal_address');
            $table->string('default_legal_basis')->default('legitimate_interest')->after('support_email');
            $table->integer('max_emails_per_hour')->default(50)->after('default_legal_basis');
            $table->boolean('require_approval_ai_contacts')->default(false)->after('max_emails_per_hour');
        });
    }

    public function down(): void
    {
        Schema::table('prospecting_settings', function (Blueprint $table) {
            $table->dropColumn([
                'postal_address',
                'support_email',
                'default_legal_basis',
                'max_emails_per_hour',
                'require_approval_ai_contacts',
            ]);
        });
    }
};
