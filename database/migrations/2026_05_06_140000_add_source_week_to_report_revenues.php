<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('report_revenues', function (Blueprint $table) {
            // Week number parsed from the source filename (e.g. "GAM - Outstream - TG - Week 05.xlsx" -> 5).
            // Source files are cumulative — each weekly export contains every day up to and including
            // that week. We keep the highest week we've seen per (source, date) so a late re-upload of
            // an earlier week can't roll back a newer week's corrected number.
            $table->unsignedSmallInteger('source_week')->nullable()->after('revenue');
            $table->index('source_week');
        });
    }

    public function down(): void
    {
        Schema::table('report_revenues', function (Blueprint $table) {
            $table->dropIndex(['source_week']);
            $table->dropColumn('source_week');
        });
    }
};
