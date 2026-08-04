<?php

namespace App\Http\Controllers;

use App\Events\NotificationCreated;
use App\Models\Notification;
use App\Models\Task;
use App\Models\TaskList;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TaskController extends Controller
{
    /**
     * Add a card to a list. The composer sends a title and nothing else —
     * everything else is set afterwards from the card detail panel.
     */
    public function store(Request $request, $list)
    {
        $list = $this->findList($list);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
        ]);

        Task::create([
            'list_id' => $list->id,
            'created_by' => Auth::id(),
            'title' => $validated['title'],
            'position' => (int) $list->tasks()->max('position') + 1,
        ]);

        return back()->with('success', 'Card added!');
    }

    /**
     * Update a card from the detail panel.
     *
     * `members` is the full set of card assignees. Anyone added is notified;
     * assignees must already be members of the board, since a card member who
     * cannot open the board would be a dead end.
     */
    public function update(Request $request, $id)
    {
        $task = $this->findTask($id);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'due_date' => 'nullable|date',
            'completed' => 'nullable|boolean',
            'members' => 'sometimes|array',
            'members.*' => 'integer|exists:users,id',
        ]);

        $task->fill([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'due_date' => $validated['due_date'] ?? null,
        ]);

        if (array_key_exists('completed', $validated)) {
            $task->completed_at = $validated['completed'] ? ($task->completed_at ?? now()) : null;
        }

        $task->save();

        $this->syncMembers($task, $validated['members'] ?? []);

        return back()->with('success', 'Card updated!');
    }

    /**
     * Rename a card in place, from the board.
     */
    public function rename(Request $request, $id)
    {
        $task = $this->findTask($id);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
        ]);

        $task->update($validated);

        return back()->with('success', 'Card renamed!');
    }

    /**
     * Complete a card, or restore it.
     *
     * Completing archives it: the card keeps its list and position, so restoring
     * puts it back exactly where it was without needing to remember anything
     * extra. Only the board query filters it out.
     */
    public function toggleComplete($id)
    {
        $task = $this->findTask($id);

        $restoring = (bool) $task->completed_at;

        $task->completed_at = $restoring ? null : now();
        $task->save();

        return back()->with('success', $restoring ? 'Card restored!' : 'Card completed!');
    }

    /**
     * Persist a card drag across the whole board.
     *
     * The client sends every card in every list it rendered. Cards outside the
     * caller's boards are ignored rather than rejected, so a stale board can
     * never write into someone else's.
     */
    public function reorder(Request $request)
    {
        $validated = $request->validate([
            'cards' => 'required|array',
            'cards.*.id' => 'required|integer',
            'cards.*.list_id' => 'required|integer',
            'cards.*.position' => 'required|integer|min:0',
        ]);

        $cardIds = collect($validated['cards'])->pluck('id');
        $listIds = collect($validated['cards'])->pluck('list_id')->unique();

        // Completed cards are excluded: they are archived, and a stale board
        // must not be able to drag one back into a list.
        $ownedCards = Task::forUser(Auth::id())
            ->whereNull('completed_at')
            ->whereIn('id', $cardIds)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $ownedLists = TaskList::forUser(Auth::id())->whereIn('id', $listIds)->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        DB::transaction(function () use ($validated, $ownedCards, $ownedLists) {
            foreach ($validated['cards'] as $row) {
                if (! in_array((int) $row['id'], $ownedCards, true)) {
                    continue;
                }
                // Never let a card be moved into a list the caller cannot see.
                if (! in_array((int) $row['list_id'], $ownedLists, true)) {
                    continue;
                }

                Task::where('id', $row['id'])->update([
                    'list_id' => $row['list_id'],
                    'position' => $row['position'],
                    'updated_at' => now(),
                ]);
            }
        });

        return back(303);
    }

    /**
     * Delete a card, or step off it.
     *
     * The creator deletes it for everyone. A card member who did not create it
     * just removes themselves, and the creator hears about it.
     */
    public function destroy($id)
    {
        $task = $this->findTask($id);

        if ((int) $task->created_by === Auth::id()) {
            $task->delete();

            return back()->with('success', 'Card deleted!');
        }

        if ($task->members()->where('users.id', Auth::id())->exists()) {
            $task->members()->detach(Auth::id());
            $this->notify(
                (int) $task->created_by,
                'task_left',
                'Someone Left A Card',
                Auth::user()->name." left your card: {$task->title}",
            );

            return back()->with('success', 'You left the card.');
        }

        // A board member who is neither creator nor assignee can still delete —
        // a shared board is a shared workspace.
        $task->delete();

        return back()->with('success', 'Card deleted!');
    }

    // ─── Helpers ──────────────────────────────────────────────────────────

    /**
     * Set the card's assignees, notifying anyone newly added.
     *
     * Candidates are intersected with the board's members first, so a payload
     * naming an outsider silently drops them instead of granting access.
     */
    private function syncMembers(Task $task, array $memberIds): void
    {
        $boardMemberIds = $task->list->board->members()->pluck('users.id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $wanted = collect($memberIds)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->filter(fn ($id) => in_array($id, $boardMemberIds, true))
            ->values();

        $current = $task->members()->pluck('users.id')->map(fn ($id) => (int) $id)->values();

        $added = $wanted->diff($current)->values();
        $removed = $current->diff($wanted)->values();

        if ($added->isNotEmpty()) {
            $task->members()->attach($added->all());

            $actor = Auth::user()->name;
            foreach ($added as $userId) {
                $this->notify(
                    $userId,
                    'task_assigned',
                    'Card Assigned To You',
                    "{$actor} assigned you a card: {$task->title}",
                );
            }
        }

        if ($removed->isNotEmpty()) {
            $task->members()->detach($removed->all());
        }
    }

    private function findTask($id): Task
    {
        return Task::forUser(Auth::id())->findOrFail($id);
    }

    private function findList($id): TaskList
    {
        return TaskList::forUser(Auth::id())->findOrFail($id);
    }

    private function notify(int $userId, string $type, string $title, string $message): void
    {
        if ($userId === Auth::id()) {
            return;
        }

        $notification = Notification::create([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'link' => route('tasks.index'),
            'actor_id' => Auth::id(),
            'is_read' => false,
        ]);

        broadcast(new NotificationCreated($notification))->toOthers();
    }
}
