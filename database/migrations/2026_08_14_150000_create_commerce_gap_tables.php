<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Promo codes (apply_discount)
        if (! Schema::hasTable('promo_codes')) {
            Schema::create('promo_codes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
                $table->uuid('uuid')->unique();
                $table->string('code');
                $table->string('type')->default('percentage'); // percentage, fixed
                $table->decimal('value', 12, 2);
                $table->decimal('min_order_amount', 12, 2)->default(0);
                $table->timestamp('starts_at')->nullable();
                $table->timestamp('ends_at')->nullable();
                $table->integer('usage_limit')->nullable();
                $table->integer('usage_count')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->unique(['organization_id', 'code']);
                $table->index(['organization_id', 'is_active']);
            });
        }

        // Persistent carts
        if (! Schema::hasTable('carts')) {
            Schema::create('carts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
                $table->uuid('uuid')->unique();
                $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
                $table->foreignId('conversation_id')->nullable()->constrained()->nullOnDelete();
                $table->string('currency')->default('NGN');
                $table->string('status')->default('active'); // active, converted, abandoned
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->index(['organization_id', 'customer_id']);
                $table->index(['organization_id', 'status']);
            });
        }

        if (! Schema::hasTable('cart_items')) {
            Schema::create('cart_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
                $table->foreignId('cart_id')->constrained()->cascadeOnDelete();
                $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('product_variant_id')->nullable()->constrained()->nullOnDelete();
                $table->integer('quantity')->default(1);
                $table->decimal('unit_price', 12, 2)->default(0);
                $table->decimal('total_price', 12, 2)->default(0);
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->index('cart_id');
            });
        }

        // Shipments (track_shipment)
        if (! Schema::hasTable('shipments')) {
            Schema::create('shipments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
                $table->uuid('uuid')->unique();
                $table->foreignId('order_id')->constrained()->cascadeOnDelete();
                $table->string('carrier')->nullable();
                $table->string('tracking_number')->nullable();
                $table->string('status')->default('pending'); // pending, dispatched, in_transit, delivered, failed
                $table->timestamp('shipped_at')->nullable();
                $table->timestamp('delivered_at')->nullable();
                $table->json('events')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->index(['organization_id', 'status']);
                $table->index(['order_id']);
            });
        }

        // Customer support tickets (distinct from platform-level support_tickets)
        if (! Schema::hasTable('customer_support_tickets')) {
            Schema::create('customer_support_tickets', function (Blueprint $table) {
                $table->id();
                $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
                $table->uuid('uuid')->unique();
                $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('conversation_id')->nullable()->constrained()->nullOnDelete();
                $table->string('subject');
                $table->text('description')->nullable();
                $table->string('status')->default('open'); // open, pending, resolved, closed
                $table->string('priority')->default('normal'); // low, normal, high, urgent
                $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('resolved_at')->nullable();
                $table->timestamps();

                $table->index(['organization_id', 'status']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_support_tickets');
        Schema::dropIfExists('shipments');
        Schema::dropIfExists('cart_items');
        Schema::dropIfExists('carts');
        Schema::dropIfExists('promo_codes');
    }
};