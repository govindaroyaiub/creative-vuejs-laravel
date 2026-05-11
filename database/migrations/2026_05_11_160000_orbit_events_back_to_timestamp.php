<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Switch orbit_events.created_at from DATETIME back to TIMESTAMP so
 * it matches the convention used by every other table in the app
 * (new_previews, bills, etc). MySQL's session-timezone auto-conversion
 * does the right thing for us — same way Previews "just works".
 *
 * Existing rows were truncated; nothing to migrate.
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
            $table->timestamp('created_at')->useCurrent();

            $table->index(['orbit_embed_id', 'type', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orbit_events');
    }
};
