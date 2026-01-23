<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rules\In;
use Inertia\Inertia;

class PreviewTrackerController extends Controller
{
    public function trackViewers(Request $request)
    {
        $pageId = $request->input('page_id');
        $cacheKey = 'viewers:preview:' . $pageId;

        $viewerName = Auth::check()
            ? Auth::user()->name
            : $request->input('guest_name', 'Guest');

        $viewers = Cache::get($cacheKey, []);
        $viewers[$viewerName] = now()->timestamp;

        Cache::put($cacheKey, $viewers, now()->addMinutes(5));

        return response()->json(['status' => 'ok']);
    }

    public function getViewers($pageId)
    {
        $cacheKey = 'viewers:preview:' . $pageId;
        $viewers = Cache::get($cacheKey, []);

        // Filter out stale viewers (10s timeout)
        $filtered = array_filter($viewers, function ($timestamp) {
            return now()->timestamp - $timestamp < 10;
        });

        // Save the cleaned up list
        Cache::put($cacheKey, $filtered, now()->addMinutes(5));

        return response()->json(array_keys($filtered));
    }

    public function index(Request $request)
    {
        $search = trim($request->input('search', ''));
        // Fetch previews with uploader, total feedbacks, approved feedbacks and last update
        $previews = \App\Models\newPreview::query()
            ->with('uploader')
            ->when($search, function ($q) use ($search) {
                $q->where(function ($q2) use ($search) {
                    $q2->where('new_previews.name', 'like', "%{$search}%")
                        ->orWhere('new_previews.slug', 'like', "%{$search}%")
                        ->orWhereHas('uploader', function ($qu) use ($search) {
                            $qu->where('name', 'like', "%{$search}%");
                        });
                });
            })
            ->select('new_previews.*')
            ->addSelect([
                'total_feedbacks' => function ($q) {
                    $q->from('new_feedback')
                        ->selectRaw('COUNT(*)')
                        ->join('new_categories', 'new_feedback.category_id', '=', 'new_categories.id')
                        ->whereColumn('new_categories.preview_id', 'new_previews.id');
                },
                'approved_feedbacks' => function ($q) {
                    $q->from('new_feedback')
                        ->selectRaw('COUNT(*)')
                        ->join('new_categories', 'new_feedback.category_id', '=', 'new_categories.id')
                        ->whereColumn('new_categories.preview_id', 'new_previews.id')
                        ->where('new_feedback.is_approved', true);
                },
                'last_feedback_updated_at' => function ($q) {
                    $q->from('new_feedback')
                        ->selectRaw('MAX(new_feedback.updated_at)')
                        ->join('new_categories', 'new_feedback.category_id', '=', 'new_categories.id')
                        ->whereColumn('new_categories.preview_id', 'new_previews.id');
                },
                'latest_feedback_description' => function ($q) {
                    $q->from('new_feedback')
                        ->select('new_feedback.description')
                        ->join('new_categories', 'new_feedback.category_id', '=', 'new_categories.id')
                        ->whereColumn('new_categories.preview_id', 'new_previews.id')
                        ->orderByDesc('new_feedback.updated_at')
                        ->limit(1);
                }
            ])
            ->orderByDesc('last_feedback_updated_at')
            ->paginate(20);

        // Normalize last_update_at to be either feedback max or preview updated_at
        $previews->getCollection()->transform(function ($p) {
            $p->last_update_at = $p->last_feedback_updated_at ?? $p->updated_at;
            return $p;
        });

        return Inertia::render('PreviewTracker/Index', [
            'previews' => $previews,
        ]);
    }
}
