<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->uuid('uuid')->unique();
            $table->string('first_name');
            $table->string('last_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('company')->nullable();
            $table->string('source')->nullable(); // website_chat, whatsapp, api, manual
            $table->string('channel')->nullable();
            $table->string('external_id')->nullable(); // ID from external channel
            $table->json('metadata')->nullable();
            $table->text('notes')->nullable();
            $table->json('tags')->nullable();
            $table->integer('lead_score')->default(0);
            $table->string('lead_stage')->nullable(); // new, contacted, qualified, etc.
            $table->timestamp('last_contacted_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['organization_id', 'email']);
            $table->index(['organization_id', 'phone']);
            $table->index(['organization_id', 'lead_stage']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};