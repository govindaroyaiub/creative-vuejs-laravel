<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Activitylog\Models\Activity;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $query = Activity::with('causer');
        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('description', 'like', '%' . $request->search . '%')
                    ->orWhere('log_name', 'like', '%' . $request->search . '%');
            });
        }

        // Allow client to request a custom per-page or "all" to show everything
        $perPage = $request->query('per_page', 15);
        if ($perPage === 'all') {
            $perPage = $query->count() ?: 1;
        }
        $perPage = is_numeric($perPage) ? intval($perPage) : 15;

        $logs = $query->orderBy('id', 'desc')->paginate($perPage);

        return Inertia::render('ActivityLogs/Index', [
            'logs' => $logs,
            'search' => $request->search,
        ]);
    }

    public function bulkDestroy(Request $request)
    {
        $data = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer',
        ]);

        $ids = $data['ids'];
        $deleted = Activity::whereIn('id', $ids)->delete();
    }

    /**
     * Delete all activity logs.
     */
    public function empty(Request $request)
    {
        // delete all activity log entries
        $deleted = Activity::query()->delete();
    }
}
