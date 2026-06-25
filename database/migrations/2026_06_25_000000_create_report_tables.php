<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Reporting module storage. Replaces the standalone Express app's per-month
     * JSON files (data/months/YYYY-MM.json). One row per site per day; the
     * partner sets vary over time, so revenue/impressions are kept as JSON
     * documents rather than one column per partner.
     */
    public function up(): void
    {
        Schema::create('report_days', function (Blueprint $table) {
            $table->id();
            $table->string('site');                       // f1maximaal | topgear | horses | festileaks
            $table->date('date');
            $table->json('revenue')->nullable();          // { adhese, gam, seedtag, teads, showheroes, adform, ogury, outbrain, preferredDeals }
            $table->json('impressions')->nullable();      // per-partner impressions (f1maximaal only carries the full set)
            $table->unsignedBigInteger('total_ad_requests')->default(0);
            $table->json('analytics')->nullable();        // GA4 metrics (f1maximaal only)
            $table->unsignedBigInteger('impressions_sold')->default(0);
            $table->timestamps();

            $table->unique(['site', 'date']);
            $table->index('date');
        });

        Schema::create('report_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->json('value')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('report_days');
        Schema::dropIfExists('report_settings');
    }
};
