<?php

namespace App\Http\Controllers;

use App\Events\NotificationCreated;
use App\Models\Notification;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class TaskController extends Controller
{
    /**
     * Display the current user's kanban board.
     *
     * A task is visible to its participants and nobody else. Shared tasks show
     * up on every participant's board; `position` comes off the pivot, so each
     * person keeps their own ordering.
     */
    public function index()
    {
        $tasks = Task::query()
            ->join('task_user', 'task_user.task_id', '=', 'tasks.id')
            ->where('task_user.user_id', Auth::id())
            ->with(['creator:id,name,email', 'participants:id,name,email'])
            ->orderBy('task_user.position')
            ->orderBy('tasks.created_at', 'desc')
            ->select('tasks.*', 'task_user.position as position')
            ->get();

        return Inertia::render('Tasks/Index', [
            'tasks' => $tasks,
            // Everyone a task can be shared with, minus yourself — you are
            // always a participant of your own tasks.
            'users' => User::where('id', '!=', Auth::id())
                ->orderBy('name')
                ->get(['id', 'name', 'email']),
        ]);
    }

    /**
     * Store a new task. Any users picked in `participants` are added alongside
     * the creator, making it a joint task rather than a handover.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => ['nullable', Rule::in(Task::STATUSES)],
            'priority' => ['nullable', Rule::in(Task::PRIORITIES)],
            'due_date' => 'nullable|date',
            'participants' => 'nullable|array',
            'participants.*' => 'integer|exists:users,id',
        ]);

        $status = $validated['status'] ?? 'todo';

        $task = Task::create([
            'created_by' => Auth::id(),
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'status' => $status,
            'priority' => $validated['priority'] ?? 'medium',
            'due_date' => $validated['due_date'] ?? null,
            'completed_at' => $status === 'done' ? now() : null,
        ]);

        // The creator is always a participant; drop them from the picked list so
        // they can't be added twice.
        $others = collect($validated['participants'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->reject(fn ($id) => $id === Auth::id())
            ->values();

        // Freshly created, so there is nothing attached yet — skip the lookup.
        $this->attachParticipants($task, collect([Auth::id()])->merge($others), []);
        $this->notifyParticipants($task, $others);

        return back()->with('success', 'Task created successfully!');
    }

    /**
     * Update a task the current user takes part in.
     *
     * `participants` is the full list of *other* people the task is shared with.
     * Anyone removed from it loses access; anyone added gets notified. The
     * caller can never remove themselves this way.
     */
    public function update(Request $request, $id)
    {
        $task = $this->findParticipating($id);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => ['required', Rule::in(Task::STATUSES)],
            'priority' => ['required', Rule::in(Task::PRIORITIES)],
            'due_date' => 'nullable|date',
            'participants' => 'nullable|array',
            'participants.*' => 'integer|exists:users,id',
        ]);

        $statusChanged = $validated['status'] !== $task->status;

        $task->fill([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'status' => $validated['status'],
            'priority' => $validated['priority'],
            'due_date' => $validated['due_date'] ?? null,
        ]);

        if ($statusChanged) {
            $task->completed_at = $validated['status'] === 'done' ? now() : null;
        }

        $task->save();

        // Status is shared, so a column change re-seats the card at the bottom
        // of that column on every participant's board.
        if ($statusChanged) {
            $this->reseatAll($task);
        }

        $wanted = collect($validated['participants'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->reject(fn ($id) => $id === Auth::id())
            ->values();

        // Read the participant list once and derive both sides from it.
        $existing = $task->participants()->pluck('users.id')->map(fn ($id) => (int) $id)->all();
        $current = collect($existing)->reject(fn ($id) => $id === Auth::id())->values();

        $added = $wanted->diff($current)->values();
        $removed = $current->diff($wanted)->values();

        if ($added->isNotEmpty()) {
            $this->attachParticipants($task, $added, $existing);
            $this->notifyParticipants($task, $added);
        }

        if ($removed->isNotEmpty()) {
            $task->participants()->detach($removed->all());
        }

        return back()->with('success', 'Task updated successfully!');
    }

    /**
     * Move a task to a column, used by the quick done toggle. Status is shared,
     * so this affects every participant's view of the card.
     */
    public function updateStatus(Request $request, $id)
    {
        $task = $this->findParticipating($id);

        $validated = $request->validate([
            'status' => ['required', Rule::in(Task::STATUSES)],
        ]);

        if ($validated['status'] !== $task->status) {
            $task->status = $validated['status'];
            $task->completed_at = $validated['status'] === 'done' ? now() : null;
            $task->save();

            $this->reseatAll($task);
        }

        return back()->with('success', 'Task moved successfully!');
    }

    /**
     * Persist a drag-and-drop reorder of the board.
     *
     * Positions are per-participant, so only the caller's pivot rows move. A
     * drag that also changes column changes the shared status, which re-seats
     * the card for the other participants.
     */
    public function reorder(Request $request)
    {
        $validated = $request->validate([
            'tasks' => 'required|array',
            'tasks.*.id' => 'required|integer',
            'tasks.*.status' => ['required', Rule::in(Task::STATUSES)],
            'tasks.*.position' => 'required|integer|min:0',
        ]);

        $ids = collect($validated['tasks'])->pluck('id');

        // Anything the caller does not take part in is ignored rather than
        // rejected, so a stale board can never touch someone else's tasks.
        $owned = Task::forUser(Auth::id())->whereIn('id', $ids)->get()->keyBy('id');

        DB::transaction(function () use ($validated, $owned) {
            foreach ($validated['tasks'] as $row) {
                $task = $owned->get($row['id']);
                if (! $task) {
                    continue;
                }

                DB::table('task_user')
                    ->where('task_id', $task->id)
                    ->where('user_id', Auth::id())
                    ->update(['position' => $row['position'], 'updated_at' => now()]);

                if ($task->status !== $row['status']) {
                    $task->status = $row['status'];
                    $task->completed_at = $row['status'] === 'done' ? now() : null;
                    $task->save();

                    $this->reseatAll($task, exceptUserId: Auth::id());
                }
            }
        });

        return back(303);
    }

    /**
     * Delete a task, or leave it.
     *
     * Only the creator can delete a shared task for everyone. Any other
     * participant just detaches themselves.
     */
    public function destroy($id)
    {
        $task = $this->findParticipating($id);

        if ($task->created_by === Auth::id()) {
            $task->delete();

            return back()->with('success', 'Task deleted successfully!');
        }

        $task->participants()->detach(Auth::id());

        // The creator loses a collaborator without doing anything, so tell them.
        $this->notifyCreatorOfLeave($task);

        return back()->with('success', 'You left the task.');
    }

    /**
     * Fetch a task the caller participates in, or 404. Never leak a task the
     * caller has no part in.
     */
    private function findParticipating($id): Task
    {
        return Task::forUser(Auth::id())->findOrFail($id);
    }

    /**
     * Attach users to a task, each appended to the bottom of the relevant
     * column on their own board. Already-attached users are skipped.
     *
     * Fixed cost: one lookup for the current participants (unless the caller
     * already has them), one grouped lookup for the bottom positions, one
     * insert — no per-user queries.
     */
    private function attachParticipants(Task $task, $userIds, ?array $existing = null): void
    {
        $existing ??= $task->participants()->pluck('users.id')->all();

        $new = collect($userIds)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->reject(fn ($id) => in_array($id, array_map('intval', $existing), true))
            ->values()
            ->all();

        if (! $new) {
            return;
        }

        $positions = $this->bottomPositions($new, $task->status, $task->id);

        $attach = [];
        foreach ($new as $userId) {
            $attach[$userId] = ['position' => $positions[$userId]];
        }

        $task->participants()->attach($attach);
    }

    /**
     * Push a task to the bottom of its column on every participant's board.
     * Used when the shared status changes.
     *
     * One grouped position lookup plus one UPDATE for all participants, rather
     * than two queries per participant.
     */
    private function reseatAll(Task $task, ?int $exceptUserId = null): void
    {
        $userIds = $task->participants()->pluck('users.id')
            ->map(fn ($id) => (int) $id)
            ->reject(fn ($id) => $exceptUserId !== null && $id === $exceptUserId)
            ->values()
            ->all();

        if (! $userIds) {
            return;
        }

        $positions = $this->bottomPositions($userIds, $task->status, $task->id);

        // Values are integers cast above, so the CASE is safe to inline; there
        // is no bindable form of a per-row UPDATE value.
        $cases = '';
        foreach ($positions as $userId => $position) {
            $cases .= sprintf(' WHEN %d THEN %d', (int) $userId, (int) $position);
        }

        DB::table('task_user')
            ->where('task_id', $task->id)
            ->whereIn('user_id', $userIds)
            ->update([
                'position' => DB::raw("CASE user_id{$cases} ELSE position END"),
                'updated_at' => now(),
            ]);
    }

    /**
     * Bottom-of-column slot for each of the given users, in one grouped query.
     *
     * `$excludeTaskId` keeps the task being moved out of its own MAX, so a card
     * re-seated repeatedly doesn't inflate its position each time.
     *
     * @param  array<int>  $userIds
     * @return array<int, int> user id => next position
     */
    private function bottomPositions(array $userIds, string $status, ?int $excludeTaskId = null): array
    {
        if (! $userIds) {
            return [];
        }

        $maxes = DB::table('task_user')
            ->join('tasks', 'tasks.id', '=', 'task_user.task_id')
            ->whereIn('task_user.user_id', $userIds)
            ->where('tasks.status', $status)
            ->when($excludeTaskId, fn ($q) => $q->where('task_user.task_id', '!=', $excludeTaskId))
            ->groupBy('task_user.user_id')
            ->selectRaw('task_user.user_id as user_id, MAX(task_user.position) as max_position')
            ->pluck('max_position', 'user_id');

        $positions = [];
        foreach ($userIds as $userId) {
            $userId = (int) $userId;
            $positions[$userId] = $maxes->has($userId) ? ((int) $maxes[$userId]) + 1 : 0;
        }

        return $positions;
    }

    /**
     * Tell the creator that a participant walked away from their task.
     *
     * Only reached from the leave path, where the caller is never the creator,
     * so there is no self-notification to guard against.
     */
    private function notifyCreatorOfLeave(Task $task): void
    {
        $notification = Notification::create([
            'user_id' => $task->created_by,
            'type' => 'task_left',
            'title' => 'Someone Left A Task',
            'message' => Auth::user()->name." left your task: {$task->title}",
            'link' => route('tasks.index'),
            'actor_id' => Auth::id(),
            'is_read' => false,
        ]);

        broadcast(new NotificationCreated($notification))->toOthers();
    }

    /**
     * Tell people a task is now shared with them. Reuses the existing in-app
     * notification system (same shape as SupportTicketController).
     */
    private function notifyParticipants(Task $task, $userIds): void
    {
        $actorName = Auth::user()->name;

        foreach ($userIds as $userId) {
            if ((int) $userId === Auth::id()) {
                continue;
            }

            $notification = Notification::create([
                'user_id' => $userId,
                'type' => 'task_assigned',
                'title' => 'Shared Task',
                'message' => "{$actorName} shared a task with you: {$task->title}",
                'link' => route('tasks.index'),
                'actor_id' => Auth::id(),
                'is_read' => false,
            ]);

            broadcast(new NotificationCreated($notification))->toOthers();
        }
    }
}
