<?php

namespace App\Http\Controllers;

use App\Events\NotificationCreated;
use App\Models\Board;
use App\Models\Notification;
use App\Models\Task;
use App\Models\TaskList;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class BoardController extends Controller
{
    /**
     * Render a board: its lists in order, each with its cards in order.
     *
     * With no board id, the user's first board opens. A user with no boards at
     * all gets one created on the spot, so /tasks is never an empty error page.
     */
    public function index(Request $request, $board = null)
    {
        $boards = Board::forUser(Auth::id())
            ->orderBy('position')
            ->orderBy('id')
            ->get(['id', 'name', 'user_id']);

        if ($boards->isEmpty()) {
            $created = $this->createBoard('My Board');
            $boards = collect([$created->only(['id', 'name', 'user_id'])])
                ->map(fn ($row) => (object) $row);
        }

        $currentId = $board ?? $boards->first()->id;

        $current = Board::forUser(Auth::id())
            ->with([
                'lists' => fn ($q) => $q->orderBy('position'),
                // Completed cards are archived: they keep their list and
                // position so restoring is just clearing the timestamp, but
                // they drop off the board itself.
                'lists.tasks' => fn ($q) => $q->whereNull('completed_at')->orderBy('position'),
                'lists.tasks.creator:id,name,email',
                'members:id,name,email',
            ])
            ->findOrFail($currentId);

        return Inertia::render('Tasks/Index', [
            'boards' => $boards,
            'board' => $current,
            'completedCards' => $this->completedCards($current->id),
            // Candidates for board membership. Card members are chosen from the
            // board's own members, so the frontend needs both lists.
            'users' => User::where('id', '!=', Auth::id())
                ->orderBy('name')
                ->get(['id', 'name', 'email']),
        ]);
    }

    /**
     * The board's archive: completed cards, newest first.
     *
     * Scoped to one board — the archive belongs to the workspace you are
     * looking at. `list_name` rides along so the panel can say where each card
     * will go back to.
     */
    private function completedCards(int $boardId)
    {
        return Task::query()
            ->join('task_lists', 'task_lists.id', '=', 'tasks.list_id')
            ->where('task_lists.board_id', $boardId)
            ->whereNotNull('tasks.completed_at')
            ->with('creator:id,name,email')
            ->orderByDesc('tasks.completed_at')
            ->select('tasks.*', 'task_lists.name as list_name')
            ->get();
    }

    /**
     * Create a board with the default lists and switch to it.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $board = $this->createBoard($validated['name']);

        return redirect()->route('tasks.board', $board->id)->with('success', 'Board created!');
    }

    public function update(Request $request, $board)
    {
        $board = $this->findOwnedBoard($board);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $board->update($validated);

        return back()->with('success', 'Board renamed!');
    }

    /**
     * Delete a board if you own it, or leave it if you were invited.
     */
    public function destroy($board)
    {
        $board = $this->findBoard($board);

        if (! $board->isOwnedBy(Auth::id())) {
            $board->members()->detach(Auth::id());
            $this->notify(
                $board->user_id,
                'board_left',
                'Someone Left A Board',
                Auth::user()->name." left your board: {$board->name}",
            );

            return redirect()->route('tasks.index')->with('success', 'You left the board.');
        }

        $board->delete();

        return redirect()->route('tasks.index')->with('success', 'Board deleted!');
    }

    /**
     * Replace the board's member list. Owner only — being able to invite is not
     * the same as being able to remove whoever invited you.
     */
    public function updateMembers(Request $request, $board)
    {
        $board = $this->findOwnedBoard($board);

        $validated = $request->validate([
            'members' => 'nullable|array',
            'members.*' => 'integer|exists:users,id',
        ]);

        $wanted = collect($validated['members'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->reject(fn ($id) => $id === (int) $board->user_id)
            ->values();

        $current = $board->members()->pluck('users.id')
            ->map(fn ($id) => (int) $id)
            ->reject(fn ($id) => $id === (int) $board->user_id)
            ->values();

        $added = $wanted->diff($current);
        $removed = $current->diff($wanted);

        if ($added->isNotEmpty()) {
            $board->members()->attach($added->all());

            $actor = Auth::user()->name;
            foreach ($added as $userId) {
                $this->notify(
                    $userId,
                    'board_shared',
                    'Board Shared With You',
                    "{$actor} added you to the board: {$board->name}",
                );
            }
        }

        if ($removed->isNotEmpty()) {
            $board->members()->detach($removed->all());
            // A removed member must not stay assigned to cards they can no
            // longer open.
            DB::table('task_user')
                ->whereIn('user_id', $removed->all())
                ->whereIn('task_id', function ($q) use ($board) {
                    $q->select('tasks.id')
                        ->from('tasks')
                        ->join('task_lists', 'task_lists.id', '=', 'tasks.list_id')
                        ->where('task_lists.board_id', $board->id);
                })
                ->delete();
        }

        return back()->with('success', 'Board members updated!');
    }

    // ─── Lists ────────────────────────────────────────────────────────────

    public function storeList(Request $request, $board)
    {
        $board = $this->findBoard($board);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        TaskList::create([
            'board_id' => $board->id,
            'name' => $validated['name'],
            'position' => (int) $board->lists()->max('position') + 1,
        ]);

        return back()->with('success', 'List added!');
    }

    public function updateList(Request $request, $list)
    {
        $list = $this->findList($list);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $list->update($validated);

        return back()->with('success', 'List renamed!');
    }

    /**
     * Deleting a list takes its cards with it, so the client confirms first.
     *
     * The seeded backbone lists are refused here, not just hidden in the UI —
     * the button being absent is not a guarantee.
     */
    public function destroyList($list)
    {
        $list = $this->findList($list);

        if ($list->is_protected) {
            return back()->with('error', "“{$list->name}” is a default list and cannot be deleted.");
        }

        $list->delete();

        return back()->with('success', 'List deleted!');
    }

    /**
     * Persist a list drag. Only lists on this board are touched; unknown ids
     * are ignored rather than rejected.
     */
    public function reorderLists(Request $request, $board)
    {
        $board = $this->findBoard($board);

        $validated = $request->validate([
            'lists' => 'required|array',
            'lists.*.id' => 'required|integer',
            'lists.*.position' => 'required|integer|min:0',
        ]);

        $ids = $board->lists()->pluck('id')->all();

        DB::transaction(function () use ($validated, $ids) {
            foreach ($validated['lists'] as $row) {
                if (! in_array((int) $row['id'], array_map('intval', $ids), true)) {
                    continue;
                }

                TaskList::where('id', $row['id'])->update(['position' => $row['position']]);
            }
        });

        return back(303);
    }

    // ─── Helpers ──────────────────────────────────────────────────────────

    /**
     * Create a board owned by the current user, seeded with the default lists.
     */
    private function createBoard(string $name): Board
    {
        return DB::transaction(function () use ($name) {
            $board = Board::create([
                'user_id' => Auth::id(),
                'name' => $name,
                'position' => (int) Board::where('user_id', Auth::id())->max('position') + 1,
            ]);

            $board->members()->attach(Auth::id());

            $index = 0;
            foreach (Board::DEFAULT_LISTS as $listName => $protected) {
                TaskList::create([
                    'board_id' => $board->id,
                    'name' => $listName,
                    'position' => $index++,
                    'is_protected' => $protected,
                ]);
            }

            return $board;
        });
    }

    private function findBoard($id): Board
    {
        return Board::forUser(Auth::id())->findOrFail($id);
    }

    private function findOwnedBoard($id): Board
    {
        return Board::forUser(Auth::id())->where('user_id', Auth::id())->findOrFail($id);
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
