<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->uuid('uuid')->unique()->after('id');
            $table->string('avatar')->nullable()->after('email_verified_at');
            $table->string('phone')->nullable()->after('avatar');
            $table->string('timezone')->default('UTC')->after('phone');
            $table->boolean('is_active')->default(true)->after('timezone');
            $table->timestamp('last_login_at')->nullable()->after('is_active');
            $table->timestamp('last_active_at')->nullable()->after('last_login_at');
            $table->softDeletes()->after('remember_token');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'uuid', 'avatar', 'phone', 'timezone',
                'is_active', 'last_login_at', 'last_active_at', 'deleted_at'
            ]);
        });
    }
};