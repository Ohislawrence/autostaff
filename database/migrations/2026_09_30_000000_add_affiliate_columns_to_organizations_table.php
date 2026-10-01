<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->string('affiliate_network', 20)->nullable();
            $table->string('affiliate_click_id', 100)->nullable();
            $table->string('affiliate_sub_id', 100)->nullable();
            $table->string('affiliate_referrer', 255)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn(['affiliate_network', 'affiliate_click_id', 'affiliate_sub_id', 'affiliate_referrer']);
        });
    }
};
