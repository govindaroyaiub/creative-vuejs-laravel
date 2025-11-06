<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Insert default timezone setting
        DB::table('scheduler_settings')->insertOrIgnore([
            'key' => 'timezone',
            'value' => 'Asia/Dhaka',
            'description' => 'Timezone for scheduler execution (defaults to Asia/Dhaka)',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('scheduler_settings')->where('key', 'timezone')->delete();
    }
};
