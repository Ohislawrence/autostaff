<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('organizations')
            ->whereNull('currency')
            ->orWhere('currency', '')
            ->update(['currency' => 'NGN']);
    }

    public function down(): void
    {
        // No-op — currency is user-editable.
    }
};
