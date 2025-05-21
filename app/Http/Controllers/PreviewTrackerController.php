<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

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
}
