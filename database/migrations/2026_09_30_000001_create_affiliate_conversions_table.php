<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('affiliate_conversions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('network', 20)->default('clicksintel');
            $table->string('click_id', 100);
            $table->string('sub_id', 100)->nullable();
            $table->string('event', 30)->default('paid_subscription');
            $table->string('plan_slug', 50)->nullable();
            $table->decimal('sale_amount', 12, 2)->nullable();
            $table->decimal('payout_amount', 12, 2)->nullable();
            $table->string('currency', 3)->default('NGN');
            $table->string('status', 20)->default('pending');
            $table->unsignedSmallInteger('response_code')->nullable();
            $table->text('response_body')->nullable();
            $table->unsignedInteger('attempts')->default(0);
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index('organization_id');
            $table->index('status');
            $table->unique(['network', 'click_id', 'event'], 'affiliate_conversions_dedupe');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('affiliate_conversions');
    }
};
