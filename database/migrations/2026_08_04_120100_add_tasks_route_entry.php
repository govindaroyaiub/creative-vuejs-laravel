<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * The `routes` table backs the permission picker in Access Manager. Without
     * a row here, admins have no way to grant `/tasks` to a user.
     */
    public function up(): void
    {
        $exists = DB::table('routes')->where('href', '/tasks')->exists();

        if (!$exists) {
            DB::table('routes')->insert([
                'title' => 'Tasks',
                'href' => '/tasks',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('routes')->where('href', '/tasks')->delete();
    }
};
