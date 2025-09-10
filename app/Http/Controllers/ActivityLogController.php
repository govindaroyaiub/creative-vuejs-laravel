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
        $logs = $query->orderBy('id', 'desc')->paginate(15);
        return Inertia::render('ActivityLogs/Index', [
            'logs' => $logs,
            'search' => $request->search,
        ]);
    }
}
