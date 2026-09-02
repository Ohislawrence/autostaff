<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scheduled_messages', function (Blueprint $table) {
            if (! Schema::hasColumn('scheduled_messages', 'attempts')) {
                $table->integer('attempts')->default(0)->after('status');
            }
            if (! Schema::hasColumn('scheduled_messages', 'max_attempts')) {
                $table->integer('max_attempts')->default(3)->after('attempts');
            }
            if (! Schema::hasColumn('scheduled_messages', 'cooldown_minutes')) {
                $table->integer('cooldown_minutes')->default(1440)->after('max_attempts');
            }
            if (! Schema::hasColumn('scheduled_messages', 'business_hours_only')) {
                $table->boolean('business_hours_only')->default(true)->after('cooldown_minutes');
            }
            if (! Schema::hasColumn('scheduled_messages', 'metadata')) {
                $table->json('metadata')->nullable()->after('error_message');
            }
            if (! Schema::hasColumn('scheduled_messages', 'last_attempt_at')) {
                $table->timestamp('last_attempt_at')->nullable()->after('metadata');
            }
        });
    }

    public function down(): void
    {
        Schema::table('scheduled_messages', function (Blueprint $table) {
            $table->dropColumn([
                'attempts',
                'max_attempts',
                'cooldown_minutes',
                'business_hours_only',
                'metadata',
                'last_attempt_at',
            ]);
        });
    }
};