<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Data repair for tasks created before sharing existed.
     *
     * Back then, picking an assignee *moved* the task: `tasks.user_id` was set
     * to the assignee, so the task disappeared off the creator's board. Those
     * rows carried that single owner through the `task_user` backfill, leaving
     * creators with no participant row for their own tasks.
     *
     * This attaches every creator back to the tasks they created, which is what
     * a joint task would have produced in the first place. Purely additive —
     * nothing is moved or removed, and re-running is a no-op.
     */
    public function up(): void
    {
        $missing = DB::table('tasks')
            ->leftJoin('task_user', function ($join) {
                $join->on('task_user.task_id', '=', 'tasks.id')
                    ->on('task_user.user_id', '=', 'tasks.created_by');
            })
            ->whereNull('task_user.id')
            ->orderBy('tasks.id')
            ->get(['tasks.id as task_id', 'tasks.created_by', 'tasks.status']);

        if ($missing->isEmpty()) {
            return;
        }

        // Bottom-of-column slot per (creator, status), in one grouped query
        // rather than one per row.
        $maxes = DB::table('task_user')
            ->join('tasks', 'tasks.id', '=', 'task_user.task_id')
            ->whereIn('task_user.user_id', $missing->pluck('created_by')->unique()->all())
            ->groupBy('task_user.user_id', 'tasks.status')
            ->selectRaw('task_user.user_id, tasks.status, MAX(task_user.position) as max_position')
            ->get()
            ->keyBy(fn ($row) => $row->user_id.'|'.$row->status);

        $next = [];
        $rows = [];
        $now = now();

        foreach ($missing as $task) {
            $key = $task->created_by.'|'.$task->status;

            if (! isset($next[$key])) {
                $next[$key] = (int) ($maxes[$key]->max_position ?? -1) + 1;
            }

            $rows[] = [
                'task_id' => $task->task_id,
                'user_id' => $task->created_by,
                'position' => $next[$key]++,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('task_user')->insert($rows);
    }

    /**
     * Reverse the migrations.
     *
     * Not reversible: once a creator is back on a task there is no way to tell
     * their restored row apart from one they added deliberately, and guessing
     * would revoke real access. Left as a no-op on purpose.
     */
    public function down(): void
    {
        // Intentionally empty — see above.
    }
};
