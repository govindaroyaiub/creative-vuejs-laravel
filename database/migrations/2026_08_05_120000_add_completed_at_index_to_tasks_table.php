<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * `completed_at` is filtered on in the board query (`whereNull`, to hide
     * archived cards), `reorder()` (`whereNull`), and the completed-cards
     * archive (`whereNotNull`, ordered by it) — all scoped by list/board. Only
     * `(list_id, position)` existed, so every one of those ran an unindexed
     * filter on top of the list lookup. `list_id` leads so it still serves
     * queries that only filter by list.
     */
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->index(['list_id', 'completed_at']);
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropIndex(['list_id', 'completed_at']);
        });
    }
};
