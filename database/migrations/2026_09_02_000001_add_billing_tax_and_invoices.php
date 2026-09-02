<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_settings', function (Blueprint $table) {
            $table->id();
            $table->decimal('vat_rate', 5, 2)->default(7.50);
            $table->json('additional_taxes')->nullable(); // [{name, rate}]
            $table->string('manual_payment_email')->nullable();
            $table->text('manual_payment_instructions')->nullable();
            $table->string('company_name')->nullable();
            $table->text('company_address')->nullable();
            $table->string('company_tax_id')->nullable(); // TIN / VAT number
            $table->string('invoice_prefix')->default('INV');
            $table->timestamps();
        });

        Schema::create('subscription_invoices', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subscription_id')->nullable()->constrained()->nullOnDelete();
            $table->string('invoice_number')->unique();
            $table->string('description');
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->string('currency')->default('NGN');
            $table->decimal('vat_rate', 5, 2)->default(0);
            $table->json('taxes')->nullable();
            $table->string('status')->default('pending'); // pending, paid, failed, cancelled
            $table->timestamp('paid_at')->nullable();
            $table->string('payment_reference')->nullable();
            $table->timestamp('due_at')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'status']);
        });

        Schema::create('subscription_payments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained('subscription_invoices')->nullOnDelete();
            $table->string('provider')->default('nomba');
            $table->string('reference')->nullable();
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('currency')->default('NGN');
            $table->string('status')->default('pending'); // pending, success, failed
            $table->text('error')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_payments');
        Schema::dropIfExists('subscription_invoices');
        Schema::dropIfExists('platform_settings');
    }
};
