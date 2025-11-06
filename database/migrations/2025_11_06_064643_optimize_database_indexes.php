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
        // Optimize new_previews table
        if (Schema::hasTable('new_previews')) {
            Schema::table('new_previews', function (Blueprint $table) {
                if (Schema::hasColumn('new_previews', 'client_id') && !$this->indexExists('new_previews', 'new_previews_client_created_idx')) {
                    $table->index(['client_id', 'created_at'], 'new_previews_client_created_idx');
                }
                if (Schema::hasColumn('new_previews', 'uploader_id') && !$this->indexExists('new_previews', 'new_previews_uploader_created_idx')) {
                    $table->index(['uploader_id', 'created_at'], 'new_previews_uploader_created_idx');
                }
                if (Schema::hasColumn('new_previews', 'requires_login') && !$this->indexExists('new_previews', 'new_previews_requires_login_idx')) {
                    $table->index(['requires_login'], 'new_previews_requires_login_idx');
                }
                if (!$this->indexExists('new_previews', 'new_previews_created_at_idx')) {
                    $table->index(['created_at'], 'new_previews_created_at_idx');
                }
                if (!$this->indexExists('new_previews', 'new_previews_updated_at_idx')) {
                    $table->index(['updated_at'], 'new_previews_updated_at_idx');
                }
            });
        }

        // Optimize bills table
        if (Schema::hasTable('bills')) {
            Schema::table('bills', function (Blueprint $table) {
                if (Schema::hasColumn('bills', 'client') && !$this->indexExists('bills', 'bills_client_idx')) {
                    $table->index(['client'], 'bills_client_idx');
                }
                if (!$this->indexExists('bills', 'bills_created_at_idx')) {
                    $table->index(['created_at'], 'bills_created_at_idx');
                }
                if (Schema::hasColumn('bills', 'total_amount') && !$this->indexExists('bills', 'bills_total_amount_idx')) {
                    $table->index(['total_amount'], 'bills_total_amount_idx');
                }
                if (Schema::hasColumn('bills', 'client') && !$this->indexExists('bills', 'bills_client_created_idx')) {
                    $table->index(['client', 'created_at'], 'bills_client_created_idx');
                }
            });
        }

        // Optimize file_transfers table
        if (Schema::hasTable('file_transfers')) {
            Schema::table('file_transfers', function (Blueprint $table) {
                if (Schema::hasColumn('file_transfers', 'user_id') && !$this->indexExists('file_transfers', 'file_transfers_user_created_idx')) {
                    $table->index(['user_id', 'created_at'], 'file_transfers_user_created_idx');
                }
                if (Schema::hasColumn('file_transfers', 'client') && !$this->indexExists('file_transfers', 'file_transfers_client_idx')) {
                    $table->index(['client'], 'file_transfers_client_idx');
                }
                if (Schema::hasColumn('file_transfers', 'client') && !$this->indexExists('file_transfers', 'file_transfers_client_created_idx')) {
                    $table->index(['client', 'created_at'], 'file_transfers_client_created_idx');
                }
                if (Schema::hasColumn('file_transfers', 'expires_at') && !$this->indexExists('file_transfers', 'file_transfers_expires_at_idx')) {
                    $table->index(['expires_at'], 'file_transfers_expires_at_idx');
                }
                if (!$this->indexExists('file_transfers', 'file_transfers_created_at_idx')) {
                    $table->index(['created_at'], 'file_transfers_created_at_idx');
                }
            });
        }

        // Optimize users table (if exists)
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (Schema::hasColumn('users', 'email_verified_at') && !$this->indexExists('users', 'users_email_verified_at_idx')) {
                    $table->index(['email_verified_at'], 'users_email_verified_at_idx');
                }
                if (!$this->indexExists('users', 'users_created_at_idx')) {
                    $table->index(['created_at'], 'users_created_at_idx');
                }
            });
        }

        // Optimize activity_log table
        if (Schema::hasTable('activity_log')) {
            Schema::table('activity_log', function (Blueprint $table) {
                if (!$this->indexExists('activity_log', 'activity_log_subject_created_idx')) {
                    $table->index(['subject_type', 'subject_id', 'created_at'], 'activity_log_subject_created_idx');
                }
                if (!$this->indexExists('activity_log', 'activity_log_causer_created_idx')) {
                    $table->index(['causer_type', 'causer_id', 'created_at'], 'activity_log_causer_created_idx');
                }
                if (Schema::hasColumn('activity_log', 'log_name') && !$this->indexExists('activity_log', 'activity_log_log_name_idx')) {
                    $table->index(['log_name'], 'activity_log_log_name_idx');
                }
            });
        }

        // Optimize sub_bills table (if exists)
        if (Schema::hasTable('sub_bills')) {
            Schema::table('sub_bills', function (Blueprint $table) {
                if (Schema::hasColumn('sub_bills', 'bill_id') && !$this->indexExists('sub_bills', 'sub_bills_bill_id_idx')) {
                    $table->index(['bill_id'], 'sub_bills_bill_id_idx');
                }
            });
        }

        // Optimize cache table
        if (Schema::hasTable('cache')) {
            Schema::table('cache', function (Blueprint $table) {
                if (Schema::hasColumn('cache', 'expiration') && !$this->indexExists('cache', 'cache_expiration_idx')) {
                    $table->index(['expiration'], 'cache_expiration_idx');
                }
            });
        }

        // Optimize jobs table
        if (Schema::hasTable('jobs')) {
            Schema::table('jobs', function (Blueprint $table) {
                if (Schema::hasColumn('jobs', 'queue') && !$this->indexExists('jobs', 'jobs_queue_created_idx')) {
                    $table->index(['queue', 'created_at'], 'jobs_queue_created_idx');
                }
                if (Schema::hasColumn('jobs', 'available_at') && !$this->indexExists('jobs', 'jobs_available_at_idx')) {
                    $table->index(['available_at'], 'jobs_available_at_idx');
                }
            });
        }
    }

    /**
     * Check if an index exists
     */
    private function indexExists(string $table, string $indexName): bool
    {
        $indexes = DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$indexName]);
        return !empty($indexes);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop indexes for new_previews
        Schema::table('new_previews', function (Blueprint $table) {
            $table->dropIndex('new_previews_client_created_idx');
            $table->dropIndex('new_previews_uploader_created_idx');
            $table->dropIndex('new_previews_requires_login_idx');
            $table->dropIndex('new_previews_created_at_idx');
            $table->dropIndex('new_previews_updated_at_idx');
        });

        // Drop indexes for bills
        Schema::table('bills', function (Blueprint $table) {
            $table->dropIndex('bills_client_idx');
            $table->dropIndex('bills_created_at_idx');
            $table->dropIndex('bills_total_amount_idx');
            $table->dropIndex('bills_client_created_idx');
        });

        // Drop indexes for file_transfers
        Schema::table('file_transfers', function (Blueprint $table) {
            $table->dropIndex('file_transfers_user_created_idx');
            $table->dropIndex('file_transfers_client_idx');
            $table->dropIndex('file_transfers_expires_at_idx');
            $table->dropIndex('file_transfers_created_at_idx');
            $table->dropIndex('file_transfers_client_created_idx');
        });

        // Drop other indexes conditionally
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropIndex('users_email_verified_at_idx');
                $table->dropIndex('users_created_at_idx');
            });
        }

        // Continue for other tables...
    }
};
