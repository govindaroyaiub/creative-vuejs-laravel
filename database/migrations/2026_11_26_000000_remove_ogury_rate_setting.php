<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Ogury revenue is now converted with daily USD→EUR rates (CurrencyRates),
     * so the old flat 'oguryRate' setting is unused.
     */
    public function up(): void
    {
        DB::table('report_settings')->where('key', 'oguryRate')->delete();
    }

    public function down(): void
    {
        // Nothing to restore: the flat rate is no longer read anywhere.
    }
};
