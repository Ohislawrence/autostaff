<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tool_executions', function (Blueprint $table) {
            $table->decimal('estimated_cost', 10, 6)->default(0)->after('execution_time_ms');
        });

        Schema::table('mcp_connections', function (Blueprint $table) {
            $table->json('pricing')->nullable()->after('rate_limit');
        });
    }

    public function down(): void
    {
        Schema::table('tool_executions', function (Blueprint $table) {
            $table->dropColumn('estimated_cost');
        });

        Schema::table('mcp_connections', function (Blueprint $table) {
            $table->dropColumn('pricing');
        });
    }
};
