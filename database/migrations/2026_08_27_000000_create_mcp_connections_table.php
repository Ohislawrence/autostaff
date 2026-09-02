<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mcp_connections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->uuid('uuid')->unique();
            $table->string('provider'); // google_calendar, gmail, crm, microsoft_365, custom
            $table->string('name');
            $table->string('transport')->default('http'); // http | stdio
            $table->string('endpoint')->nullable();
            $table->json('command')->nullable(); // stdio command + args
            $table->json('env')->nullable(); // stdio env (non-secret)
            $table->json('headers')->nullable(); // non-secret HTTP headers
            $table->text('credentials')->nullable(); // encrypted JSON (tokens, etc.)
            $table->json('scopes')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->integer('timeout')->default(30);
            $table->string('status')->default('disconnected');
            $table->boolean('is_connected')->default(false);
            $table->timestamp('last_health_check_at')->nullable();
            $table->text('last_error')->nullable();
            $table->json('rate_limit')->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'provider']);
            $table->index(['organization_id', 'is_connected']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mcp_connections');
    }
};
