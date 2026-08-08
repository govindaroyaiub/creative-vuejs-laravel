<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Remove the Orbit banner-embed feature.
 *
 * Orbit let a banner be embedded on a third-party page via
 * `/tag/banner/{id}.js` and recorded view/click beacons against it. Both
 * tables were empty in production, so nothing is lost.
 *
 * `orbit_events` is dropped first: it holds a cascading foreign key to
 * `orbit_embeds`, and MySQL will not drop a table another table references.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('orbit_events');
        Schema::dropIfExists('orbit_embeds');

        // The sidebar entry is seeded data, so it survives in existing
        // databases even after the seeder stops emitting it.
        DB::table('routes')->where('href', '/orbit')->delete();
    }

    /**
     * Not reversible — the feature's code is gone, so recreating empty tables
     * would serve no purpose. Matches `drop_legacy_report_tables`.
     */
    public function down(): void
    {
    }
};
