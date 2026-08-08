<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Drop indexes that cost write time on every insert and update without ever
 * being usable by a query.
 *
 * Two groups:
 *
 *  1. Duplicates. `add_essential_indexes_only` re-created indexes that
 *     `optimize_database_indexes` had made an hour earlier, under `idx_*`
 *     names instead of `*_idx`, inside try/catch blocks that swallowed the
 *     "already exists" errors. That migration has been deleted.
 *
 *  2. Indexes no query can use. Most were added for search boxes that match
 *     with `LIKE '%term%'` — a leading wildcard cannot use a B-tree index at
 *     all — or for columns nothing filters on.
 *
 * `optimize_database_indexes` has been rewritten to declare only the surviving
 * set, so a fresh install never creates any of these. This removes them from
 * databases that already ran the originals. Existence is checked because a
 * fresh database will not have them.
 */
return new class extends Migration
{
    /** table => [index name => why it is redundant] */
    private const REDUNDANT = [
        'bills' => [
            'idx_bills_client' => 'duplicate of bills_client_idx',
            'idx_bills_created_at' => 'duplicate of bills_created_at_idx',
            'bills_client_idx' => 'client is only ever matched with LIKE %term%',
            'bills_client_created_idx' => 'client leads, and it is only matched with LIKE %term%',
            'bills_total_amount_idx' => 'total_amount is only matched with LIKE %term%',
        ],
        'new_previews' => [
            'idx_new_previews_created_at' => 'duplicate of new_previews_created_at_idx',
            'idx_new_previews_slug' => 'slug is already covered by new_previews_slug_unique',
            'new_previews_requires_login_idx' => 'boolean, two distinct values, and nothing filters on it',
            'new_previews_updated_at_idx' => 'no query touches updated_at',
        ],
        'file_transfers' => [
            'idx_file_transfers_created_at' => 'duplicate of file_transfers_created_at_idx',
            'file_transfers_client_idx' => 'client is only ever matched with LIKE %term%',
            'file_transfers_client_created_idx' => 'client leads, and it is only matched with LIKE %term%',
            'file_transfers_user_created_idx' => 'no query filters file_transfers by user_id',
        ],
        'users' => [
            'users_email_verified_at_idx' => 'nothing filters users by email_verified_at',
            'users_created_at_idx' => 'nothing filters or orders users by created_at',
        ],
        'activity_log' => [
            'activity_log_log_name_idx' => "duplicate of Spatie's activity_log_log_name_index",
            'activity_log_causer_created_idx' => 'nothing filters by causer; Spatie already indexes causer',
        ],
    ];

    public function up(): void
    {
        foreach (self::REDUNDANT as $table => $reasons) {
            $existing = collect(Schema::getIndexes($table))
                ->map(fn ($index) => strtolower((string) $index['name']))
                ->all();

            $drop = array_filter(
                array_keys($reasons),
                fn ($name) => in_array(strtolower($name), $existing, true),
            );

            if ($drop === []) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($drop) {
                foreach ($drop as $name) {
                    $blueprint->dropIndex($name);
                }
            });
        }
    }

    /**
     * Not reversible: recreating these would only restore the write overhead.
     */
    public function down(): void
    {
    }
};
