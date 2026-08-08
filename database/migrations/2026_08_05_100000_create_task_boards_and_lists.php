<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Restructure tasks into a Trello-shaped model.
     *
     *   board ──< task_list ──< task
     *     └──< board_user (members)          task ──< task_user (card members)
     *
     * Lists replace the fixed `status` enum, so users name their own columns.
     * `priority` goes away entirely. Positions move back onto `tasks` and off
     * `task_user`: everyone on a board sees the same order, as in Trello.
     */
    public function up(): void
    {
        Schema::create('boards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // owner
            $table->string('name');
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->index(['user_id', 'position']);
        });

        Schema::create('board_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('board_id')->constrained('boards')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['board_id', 'user_id']);
            $table->index('user_id');
        });

        // `list` is a PHP reserved word, hence task_lists / TaskList.
        Schema::create('task_lists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('board_id')->constrained('boards')->onDelete('cascade');
            $table->string('name');
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->index(['board_id', 'position']);
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->foreignId('list_id')->nullable()->after('id')->constrained('task_lists')->onDelete('cascade');
            $table->unsignedInteger('position')->default(0)->after('due_date');
        });

        $this->backfill();

        // Old shape: status/priority columns and per-user pivot positions.
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropIndex(['status', 'priority']);
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn(['status', 'priority']);
        });

        // `create_task_user_table` indexed ['user_id', 'position'], and the
        // `position` column is going away. Two constraints make the order here
        // matter:
        //
        //  - MySQL requires an index covering a foreign key column. It never
        //    created a dedicated one for `user_id` because ['user_id',
        //    'position'] already served as one, so dropping that index while
        //    it is the only candidate fails with errno 1553. A standalone
        //    `user_id` index has to exist first.
        //  - SQLite refuses to drop a column an index still references, so
        //    the composite index has to go before the column.
        Schema::table('task_user', function (Blueprint $table) {
            $table->index('user_id');
        });

        Schema::table('task_user', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'position']);
        });

        Schema::table('task_user', function (Blueprint $table) {
            $table->dropColumn('position');
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->index(['list_id', 'position']);
        });
    }

    /**
     * Give every existing task a home: one board per creator, the four default
     * lists, and their cards dropped into the first list. Anyone who was a
     * participant on those cards becomes a member of that board, so a shared
     * card stays visible to them.
     */
    private function backfill(): void
    {
        $tasks = DB::table('tasks')->orderBy('id')->get(['id', 'created_by']);

        if ($tasks->isEmpty()) {
            return;
        }

        $now = now();
        $defaults = ['Today', 'Tomorrow', 'This week', 'Later'];

        foreach ($tasks->groupBy('created_by') as $creatorId => $creatorTasks) {
            $boardId = DB::table('boards')->insertGetId([
                'user_id' => $creatorId,
                'name' => 'My Board',
                'position' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('board_user')->insert([
                'board_id' => $boardId,
                'user_id' => $creatorId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $listIds = [];
            foreach ($defaults as $index => $name) {
                $listIds[] = DB::table('task_lists')->insertGetId([
                    'board_id' => $boardId,
                    'name' => $name,
                    'position' => $index,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            $taskIds = $creatorTasks->pluck('id')->all();

            foreach (array_values($taskIds) as $position => $taskId) {
                DB::table('tasks')->where('id', $taskId)->update([
                    'list_id' => $listIds[0],
                    'position' => $position,
                ]);
            }

            // Card participants need board access to keep seeing the card.
            $participants = DB::table('task_user')
                ->whereIn('task_id', $taskIds)
                ->pluck('user_id')
                ->unique()
                ->reject(fn ($id) => (int) $id === (int) $creatorId)
                ->values();

            foreach ($participants as $userId) {
                DB::table('board_user')->insertOrIgnore([
                    'board_id' => $boardId,
                    'user_id' => $userId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * Lists collapse back to the old three-state enum, which cannot be
     * reconstructed from arbitrary user list names — everything lands on
     * `todo` at default priority.
     */
    public function down(): void
    {
        Schema::table('task_user', function (Blueprint $table) {
            $table->unsignedInteger('position')->default(0)->after('user_id');
        });

        // Restore the composite index `up()` dropped, so rollback-then-migrate
        // works. It has to go back before the standalone `user_id` index is
        // removed, or MySQL is left with no index for the foreign key.
        Schema::table('task_user', function (Blueprint $table) {
            $table->index(['user_id', 'position']);
        });

        Schema::table('task_user', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
        });

        // The `list_id` foreign key has to go before its index: MySQL is using
        // ['list_id', 'position'] to satisfy that key, and refuses to drop the
        // index while the constraint still needs it (errno 1553).
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropForeign(['list_id']);
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->dropIndex(['list_id', 'position']);
            $table->enum('status', ['todo', 'in_progress', 'done'])->default('todo')->after('description');
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium')->after('status');
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->index(['status', 'priority']);
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn(['list_id', 'position']);
        });

        Schema::dropIfExists('task_lists');
        Schema::dropIfExists('board_user');
        Schema::dropIfExists('boards');
    }
};
