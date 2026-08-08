<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Reporting/listing indexes, declared in one table.
 *
 * Every entry is `index name => columns`. Adding one is a single line, and
 * `down()` derives itself from the same list, so the two cannot drift.
 *
 * There are no hasTable()/hasColumn()/indexExists() guards: a migration runs
 * once against a schema built by the migrations before it, so the tables and
 * columns are known to exist. The guards this file used to carry hid real
 * problems — one branch tested for `file_transfers.expires_at`, a column that
 * has never existed, so it silently never ran.
 */
return new class extends Migration
{
    private const INDEXES = [
        'new_previews' => [
            // NewPreviewController@index filters client_id (non-Planet-Nine
            // users) and uploader_id, ordering by created_at desc.
            'new_previews_client_created_idx' => ['client_id', 'created_at'],
            'new_previews_uploader_created_idx' => ['uploader_id', 'created_at'],
            'new_previews_created_at_idx' => ['created_at'],
        ],
        'bills' => [
            // BillController@index orders by created_at.
            'bills_created_at_idx' => ['created_at'],
        ],
        'file_transfers' => [
            // FileTransferController@index uses latest().
            'file_transfers_created_at_idx' => ['created_at'],
        ],
        // `log_name` is left alone: Spatie's own migration already indexes it.
        // NewPreviewController@activityLog filters subject_type + subject_id.
        'activity_log' => [
            'activity_log_subject_created_idx' => ['subject_type', 'subject_id', 'created_at'],
        ],
        'sub_bills' => [
            'sub_bills_bill_id_idx' => ['bill_id'],
        ],
        'cache' => [
            'cache_expiration_idx' => ['expiration'],
        ],
        'jobs' => [
            'jobs_queue_created_idx' => ['queue', 'created_at'],
            'jobs_available_at_idx' => ['available_at'],
        ],
    ];

    public function up(): void
    {
        foreach (self::INDEXES as $table => $indexes) {
            Schema::table($table, function (Blueprint $blueprint) use ($indexes) {
                foreach ($indexes as $name => $columns) {
                    $blueprint->index($columns, $name);
                }
            });
        }
    }

    /**
     * Indexes whose leading column is a foreign key. MySQL requires every
     * foreign key to be covered by an index, and it did not create its own for
     * these columns because the index above already served that purpose — so
     * dropping one directly fails with errno 1553. The foreign key is removed
     * first and re-added afterwards, which lets MySQL rebuild its own index.
     *
     * `index name => [column, referenced table]`. All three cascade on delete.
     */
    private const FOREIGN_KEY_BACKED = [
        'new_previews' => [
            'new_previews_client_created_idx' => ['client_id', 'clients'],
            'new_previews_uploader_created_idx' => ['uploader_id', 'users'],
        ],
        'sub_bills' => [
            'sub_bills_bill_id_idx' => ['bill_id', 'bills'],
        ],
    ];

    public function down(): void
    {
        foreach (self::INDEXES as $table => $indexes) {
            $fkBacked = self::FOREIGN_KEY_BACKED[$table] ?? [];

            foreach ($fkBacked as $name => [$column, $references]) {
                Schema::table($table, fn (Blueprint $blueprint) => $blueprint->dropForeign([$column]));
                Schema::table($table, fn (Blueprint $blueprint) => $blueprint->dropIndex($name));
                Schema::table($table, fn (Blueprint $blueprint) => $blueprint->foreign($column)
                    ->references('id')->on($references)->cascadeOnDelete());
            }

            $plain = array_diff(array_keys($indexes), array_keys($fkBacked));

            if ($plain === []) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($plain) {
                foreach ($plain as $name) {
                    $blueprint->dropIndex($name);
                }
            });
        }
    }
};
