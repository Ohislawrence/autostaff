<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plugin_installations', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plugin_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plugin_version_id')->nullable()->constrained()->nullOnDelete();
            $table->string('site_url');
            $table->string('site_label')->nullable();
            $table->foreignId('api_key_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->default('active'); // active, revoked, suspended
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'plugin_id']);
        });

        Schema::table('api_keys', function (Blueprint $table) {
            $table->foreignId('plugin_installation_id')->nullable()->after('organization_id')->constrained()->nullOnDelete();
        });

        Schema::table('plugins', function (Blueprint $table) {
            $table->json('scopes')->nullable()->after('max_api_version');
        });
    }

    public function down(): void
    {
        Schema::table('plugins', function (Blueprint $table) {
            $table->dropColumn('scopes');
        });

        Schema::table('api_keys', function (Blueprint $table) {
            $table->dropConstrainedForeignId('plugin_installation_id');
        });

        Schema::dropIfExists('plugin_installations');
    }
};
