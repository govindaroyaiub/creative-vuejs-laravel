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
        // Update existing 'resolved' and 'closed' to 'done'
        DB::table('support_tickets')
            ->whereIn('status', ['resolved', 'closed'])
            ->update(['status' => 'done']);

        // Modify the enum to use the new values
        DB::statement("ALTER TABLE support_tickets MODIFY COLUMN status ENUM('pending', 'in_progress', 'done') NOT NULL DEFAULT 'pending'");

        // Update any remaining 'open' to 'pending'
        DB::table('support_tickets')
            ->where('status', 'open')
            ->update(['status' => 'pending']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back to old status values
        DB::statement("ALTER TABLE support_tickets MODIFY COLUMN status ENUM('open', 'in_progress', 'resolved', 'closed') NOT NULL DEFAULT 'open'");

        DB::table('support_tickets')
            ->where('status', 'pending')
            ->update(['status' => 'open']);

        DB::table('support_tickets')
            ->where('status', 'done')
            ->update(['status' => 'resolved']);
    }
};
