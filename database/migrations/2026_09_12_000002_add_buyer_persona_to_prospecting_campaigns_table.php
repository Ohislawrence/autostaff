<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prospecting_campaigns', function (Blueprint $table) {
            $table->foreignId('buyer_persona_id')->nullable()->after('icp')->constrained('buyer_personas')->nullOnDelete();
            $table->json('buyer_persona_snapshot')->nullable()->after('buyer_persona_id');
        });
    }

    public function down(): void
    {
        Schema::table('prospecting_campaigns', function (Blueprint $table) {
            $table->dropConstrainedForeignId('buyer_persona_id');
            $table->dropColumn('buyer_persona_snapshot');
        });
    }
};
