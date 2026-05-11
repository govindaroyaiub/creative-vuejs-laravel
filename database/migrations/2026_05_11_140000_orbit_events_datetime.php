<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * orbit_events.created_at was a TIMESTAMP, which makes MySQL
 * auto-convert between session timezone and stored UTC. Combined
 * with the DB session being on SYSTEM time and Laravel writing
 * Carbon::now() (in app.timezone), the stored "UTC moment" was
 * shifted by the session offset and period filters in the user's
 * local timezone never matched.
 *
 * Switching the column to DATETIME removes the auto-conversion —
 * we always store and query in UTC, period boundaries are converted
 * from the user's timezone to UTC in the controller.
 *
 * Existing rows were stored under the broken regime, so we drop
 * the table and recreate it. Dev only — no real metrics yet.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('orbit_events');

        Schema::create('orbit_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('orbit_embed_id')
                ->constrained('orbit_embeds')
                ->cascadeOnDelete();
            $table->string('type', 16);
            $table->string('referrer', 1024)->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 1024)->nullable();
            $table->dateTime('created_at');

            $table->index(['orbit_embed_id', 'type', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orbit_events');
    }
};
