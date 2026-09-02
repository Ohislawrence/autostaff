<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->decimal('usd_price', 10, 2)->nullable()->after('price');
        });

        // Nomba is the default payment provider — base currency is NGN.
        // usd_price keeps USD support for clients outside Nigeria.
        DB::table('plans')->update(['currency' => 'NGN']);
        DB::table('plans')->where('slug', 'starter')->update(['price' => 45000, 'usd_price' => 29]);
        DB::table('plans')->where('slug', 'business')->update(['price' => 150000, 'usd_price' => 99]);
        DB::table('plans')->where('slug', 'professional')->update(['price' => 375000, 'usd_price' => 249]);
        DB::table('plans')->where('slug', 'enterprise')->update(['price' => 0, 'usd_price' => 0]);
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn('usd_price');
        });
    }
};
