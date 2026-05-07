<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Remove the stale "Generate Reports" registry row left over from
        // the old generator and replace it with the new "Check Reports"
        // entry. Idempotent — safe to run on fresh DBs (deletes nothing,
        // upserts the new row).
        DB::table('routes')->where('href', '/generate/reports')->delete();

        if (!DB::table('routes')->where('href', '/reports/checks')->exists()) {
            DB::table('routes')->insert([
                'title'      => 'Check Reports',
                'href'       => '/reports/checks',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('routes')->where('href', '/reports/checks')->delete();
    }
};
