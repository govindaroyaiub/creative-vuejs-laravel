<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tasks move from single-owner to shared: a task can sit on several boards
     * at once. `task_user` is the participant list, and it carries `position`
     * because each participant orders their own board independently.
     *
     * Existing rows are backfilled from `tasks.user_id` / `tasks.position`
     * before those columns are dropped, so no task or ordering is lost.
     */
    public function up(): void
    {
        Schema::create('task_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('tasks')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->unique(['task_id', 'user_id']);
            $table->index(['user_id', 'position']);
        });

        // Backfill: every existing task becomes a one-participant shared task.
        DB::table('tasks')
            ->orderBy('id')
            ->select('id', 'user_id', 'position')
            ->chunk(200, function ($tasks) {
                $now = now();
                $rows = [];

                foreach ($tasks as $task) {
                    $rows[] = [
                        'task_id' => $task->id,
                        'user_id' => $task->user_id,
                        'position' => $task->position,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                if ($rows) {
                    DB::table('task_user')->insert($rows);
                }
            });

        // Each step is its own ALTER so MySQL sees them in this exact order:
        // the foreign key is backed by the composite index, so dropping the
        // index first fails with errno 1553.
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'status', 'position']);
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn(['user_id', 'position']);
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->index(['status', 'priority']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * A shared task can only collapse back to one owner, so the creator's
     * participant row wins; any other participant row is dropped.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropIndex(['status', 'priority']);
            $table->foreignId('user_id')->nullable()->after('id')->constrained('users')->onDelete('cascade');
            $table->unsignedInteger('position')->default(0)->after('due_date');
        });

        DB::table('tasks')->orderBy('id')->select('id', 'created_by')->chunk(200, function ($tasks) {
            foreach ($tasks as $task) {
                $pivot = DB::table('task_user')
                    ->where('task_id', $task->id)
                    ->orderByRaw('user_id = ? DESC', [$task->created_by])
                    ->first();

                if ($pivot) {
                    DB::table('tasks')->where('id', $task->id)->update([
                        'user_id' => $pivot->user_id,
                        'position' => $pivot->position,
                    ]);
                }
            }
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->index(['user_id', 'status', 'position']);
        });

        Schema::dropIfExists('task_user');
    }
};
