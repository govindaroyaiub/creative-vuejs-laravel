<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Mark the seeded lists that cannot be deleted.
     *
     * A flag rather than a name check: lists stay renameable, and renaming one
     * must not be a way to sneak past the protection.
     */
    public function up(): void
    {
        Schema::table('task_lists', function (Blueprint $table) {
            $table->boolean('is_protected')->default(false)->after('position');
        });

        DB::table('task_lists')
            ->whereIn('name', ['Today', 'Tomorrow', 'This week', 'Later'])
            ->update(['is_protected' => true]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('task_lists', function (Blueprint $table) {
            $table->dropColumn('is_protected');
        });
    }
};
