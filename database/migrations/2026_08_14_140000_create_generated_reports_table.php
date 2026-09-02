<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('generated_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->uuid('uuid')->unique();
            $table->string('period'); // daily, weekly, monthly
            $table->date('period_start');
            $table->date('period_end');
            $table->json('metrics');
            $table->text('summary_text')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'period']);
            $table->index(['organization_id', 'period_end']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('generated_reports');
    }
};