<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('buyer_personas', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('organization_id')->index()->constrained('organizations')->cascadeOnDelete();
            $table->string('name');
            $table->string('avatar', 10)->nullable();
            $table->json('role_titles')->nullable();
            $table->json('demographics')->nullable();
            $table->json('goals')->nullable();
            $table->json('pains')->nullable();
            $table->json('objections')->nullable();
            $table->json('buying_triggers')->nullable();
            $table->json('messaging_hooks')->nullable();
            $table->json('value_props')->nullable();
            $table->json('channels')->nullable();
            $table->text('current_solution')->nullable();
            $table->json('keywords')->nullable();
            $table->boolean('is_template')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('buyer_personas');
    }
};
