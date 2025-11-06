<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add only essential indexes that likely don't exist yet

        try {
            // Add slug index for new_previews
            if (Schema::hasTable('new_previews') && Schema::hasColumn('new_previews', 'slug')) {
                DB::statement('CREATE INDEX idx_new_previews_slug ON new_previews(slug)');
            }
        } catch (\Exception $e) {
            // Index might already exist, continue
        }

        try {
            // Add client index for bills
            if (Schema::hasTable('bills') && Schema::hasColumn('bills', 'client')) {
                DB::statement('CREATE INDEX idx_bills_client ON bills(client)');
            }
        } catch (\Exception $e) {
            // Index might already exist, continue
        }

        try {
            // Add expires_at index for file_transfers
            if (Schema::hasTable('file_transfers') && Schema::hasColumn('file_transfers', 'expires_at')) {
                DB::statement('CREATE INDEX idx_file_transfers_expires_at ON file_transfers(expires_at)');
            }
        } catch (\Exception $e) {
            // Index might already exist, continue
        }

        // Add created_at indexes for performance queries
        $tables = ['new_previews', 'bills', 'file_transfers'];
        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                try {
                    DB::statement("CREATE INDEX idx_{$table}_created_at ON {$table}(created_at)");
                } catch (\Exception $e) {
                    // Index might already exist, continue
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop the indexes if they exist
        if (Schema::hasTable('new_previews')) {
            DB::statement('DROP INDEX IF EXISTS idx_new_previews_slug ON new_previews');
            DB::statement('DROP INDEX IF EXISTS idx_new_previews_created_at ON new_previews');
        }

        if (Schema::hasTable('bills')) {
            DB::statement('DROP INDEX IF EXISTS idx_bills_client ON bills');
            DB::statement('DROP INDEX IF EXISTS idx_bills_created_at ON bills');
        }

        if (Schema::hasTable('file_transfers')) {
            DB::statement('DROP INDEX IF EXISTS idx_file_transfers_expires_at ON file_transfers');
            DB::statement('DROP INDEX IF EXISTS idx_file_transfers_created_at ON file_transfers');
        }
    }
};
