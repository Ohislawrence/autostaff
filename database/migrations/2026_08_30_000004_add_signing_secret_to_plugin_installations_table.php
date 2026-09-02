<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plugin_installations', function (Blueprint $table) {
            $table->string('signing_secret')->nullable()->after('api_key_id');
        });
    }

    public function down(): void
    {
        Schema::table('plugin_installations', function (Blueprint $table) {
            $table->dropColumn('signing_secret');
        });
    }
};
