<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mcp_connections', function (Blueprint $table) {
            $table->unsignedInteger('consecutive_failures')->default(0)->after('last_error');
            $table->timestamp('circuit_open_until')->nullable()->after('consecutive_failures');
        });
    }

    public function down(): void
    {
        Schema::table('mcp_connections', function (Blueprint $table) {
            $table->dropColumn(['circuit_open_until', 'consecutive_failures']);
        });
    }
};
