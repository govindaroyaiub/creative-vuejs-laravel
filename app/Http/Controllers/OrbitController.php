<?php

namespace App\Http\Controllers;

use App\Models\OrbitEmbed;
use App\Models\OrbitEvent;
use App\Models\newBanner;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class OrbitController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->input('search', ''));
        [$period, $start, $end] = $this->resolvePeriod($request);

        $startDate = $start->toDateString();
        $endDate   = $end->toDateString();

        // Every embed shows up regardless of activity; the per-row counts
        // and the totals reflect the selected period. Newly published
        // embeds with no events yet still appear (with 0/0), which is
        // what you want for an inventory view.
        $query = OrbitEmbed::query()
            ->with([
                'banner.size',
                'banner.version.feedbackset.feedback.category.preview',
            ])
            ->withCount([
                'events as impressions_count' => fn ($q) => $q->where('type', 'view')
                    ->whereDate('created_at', '>=', $startDate)
                    ->whereDate('created_at', '<=', $endDate),
                'events as clicks_count' => fn ($q) => $q->where('type', 'click')
                    ->whereDate('created_at', '>=', $startDate)
                    ->whereDate('created_at', '<=', $endDate),
            ])
            ->orderByDesc('created_at');

        $embeds = $query->paginate(15)->withQueryString();

        $embeds->getCollection()->transform(function (OrbitEmbed $embed) {
            $banner = $embed->banner;

            return [
                'id'                => $embed->id,
                'banner_id'         => $banner?->id,
                'banner_name'       => $banner?->name,
                'preview_name'      => $banner?->preview_name,
                'width'             => $banner?->size?->width,
                'height'            => $banner?->size?->height,
                'tag_url'           => $banner ? url("/tag/banner/{$banner->id}.js") : null,
                'snippet'           => $banner
                    ? '<script src="' . url("/tag/banner/{$banner->id}.js") . '" async></script>'
                    : null,
                'impressions_count' => (int) ($embed->impressions_count ?? 0),
                'clicks_count'      => (int) ($embed->clicks_count ?? 0),
                'is_active'         => (bool) $embed->is_active,
                'created_at'        => $embed->created_at,
            ];
        });

        if ($search !== '') {
            $needle = mb_strtolower($search);
            $filtered = $embeds->getCollection()->filter(function ($row) use ($needle) {
                return str_contains(mb_strtolower((string) $row['banner_name']), $needle)
                    || str_contains(mb_strtolower((string) $row['preview_name']), $needle);
            })->values();
            $embeds->setCollection($filtered);
        }

        $totals = [
            'impressions' => (int) OrbitEvent::where('type', 'view')
                ->whereDate('created_at', '>=', $startDate)
                ->whereDate('created_at', '<=', $endDate)
                ->count(),
            'clicks' => (int) OrbitEvent::where('type', 'click')
                ->whereDate('created_at', '>=', $startDate)
                ->whereDate('created_at', '<=', $endDate)
                ->count(),
            'embeds' => (int) OrbitEmbed::count(),
        ];

        return Inertia::render('Orbit/Index', [
            'embeds' => $embeds,
            'search' => $search,
            'totals' => $totals,
            'period' => [
                'value' => $period,
                'from'  => $start->toDateString(),
                'to'    => $end->toDateString(),
                'label' => $this->periodLabel($period, $start, $end),
            ],
        ]);
    }

    /**
     * Resolves the active reporting window from the request. All
     * boundaries are calendar dates in the app's timezone — same
     * convention as the rest of the app (Previews, Bills, etc).
     * Default period is 'month'; invalid values fall back to 'month'.
     */
    private function resolvePeriod(Request $request): array
    {
        $period = (string) $request->input('period', 'month');
        $now    = now();

        if (! in_array($period, ['today', 'week', 'month', 'year', 'custom'], true)) {
            $period = 'month';
        }

        if ($period === 'custom') {
            try {
                $from = $request->input('from');
                $to   = $request->input('to');
                $start = $from ? Carbon::parse($from)->startOfDay() : $now->copy()->startOfMonth();
                $end   = $to   ? Carbon::parse($to)->endOfDay()     : $now->copy()->endOfMonth();
                if ($start->gt($end)) {
                    [$start, $end] = [$end->copy()->startOfDay(), $start->copy()->endOfDay()];
                }
                return [$period, $start, $end];
            } catch (\Throwable $e) {
                $period = 'month';
            }
        }

        [$start, $end] = match ($period) {
            'today' => [$now->copy()->startOfDay(),  $now->copy()->endOfDay()],
            'week'  => [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()],
            'year'  => [$now->copy()->startOfYear(), $now->copy()->endOfYear()],
            default => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
        };

        return [$period, $start, $end];
    }

    private function periodLabel(string $period, Carbon $start, Carbon $end): string
    {
        return match ($period) {
            'today'  => $start->format('D, j M Y'),
            'week'   => $start->format('j M') . ' – ' . $end->format('j M Y'),
            'year'   => $start->format('Y'),
            'custom' => $start->format('j M Y') . ' – ' . $end->format('j M Y'),
            default  => $start->format('F Y'),
        };
    }

    /**
     * Modal step 1: 10 most recent previews that still have at least one
     * banner not yet added to Orbit. The frontend renders this list first,
     * then drills into one via availableBannersForPreview().
     */
    public function availablePreviews()
    {
        $taken = OrbitEmbed::pluck('banner_id')->all();

        // Get all banners not yet in Orbit, grouped by their preview.
        // Practical for typical inventory sizes (hundreds of banners); if
        // it ever grows much larger, swap for a join query.
        $banners = newBanner::with(['version.feedbackset.feedback.category.preview'])
            ->whereNotIn('id', $taken)
            ->get();

        $previews = $banners
            ->groupBy(fn ($b) => $b->version?->feedbackset?->feedback?->category?->preview_id)
            ->filter(fn ($_, $previewId) => $previewId !== null)
            ->map(function ($group) {
                $preview = $group->first()
                    ->version?->feedbackset?->feedback?->category?->preview;
                if (! $preview) return null;
                return [
                    'id'                     => $preview->id,
                    'name'                   => $preview->name,
                    'created_at'             => $preview->created_at,
                    'available_banner_count' => $group->count(),
                ];
            })
            ->filter()
            ->sortByDesc('created_at')
            ->take(10)
            ->values()
            ->map(fn ($p) => [
                'id'                     => $p['id'],
                'name'                   => $p['name'],
                'available_banner_count' => $p['available_banner_count'],
            ]);

        return response()->json(['previews' => $previews]);
    }

    /**
     * Modal step 2: list of not-yet-Orbited banners belonging to a single
     * preview, used after the user clicks a preview in step 1.
     */
    public function availableBannersForPreview($previewId)
    {
        $taken = OrbitEmbed::pluck('banner_id')->all();

        $banners = newBanner::query()
            ->with(['size'])
            ->whereNotIn('new_banners.id', $taken)
            ->whereHas('version.feedbackset.feedback.category',
                fn ($q) => $q->where('preview_id', $previewId))
            ->orderBy('position')
            ->orderBy('id')
            ->get()
            ->map(fn (newBanner $b) => [
                'id'     => $b->id,
                'name'   => $b->name,
                'width'  => $b->size?->width,
                'height' => $b->size?->height,
            ]);

        return response()->json(['banners' => $banners]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'banner_ids'   => ['required', 'array', 'min:1', 'max:200'],
            'banner_ids.*' => ['integer', 'exists:new_banners,id', 'distinct'],
        ]);

        // Skip banners that already have an embed rather than failing the
        // whole request — the picker may race against another tab adding
        // the same banner. Adds the rest.
        $alreadyIn = OrbitEmbed::whereIn('banner_id', $data['banner_ids'])
            ->pluck('banner_id')->all();
        $toAdd = array_diff($data['banner_ids'], $alreadyIn);

        foreach ($toAdd as $bannerId) {
            OrbitEmbed::create(['banner_id' => (int) $bannerId]);
        }

        return redirect()->route('orbit.index');
    }

    public function destroy($id)
    {
        $embed = OrbitEmbed::findOrFail($id);
        $embed->delete();

        return redirect()->route('orbit.index');
    }

    public function toggle($id)
    {
        $embed = OrbitEmbed::findOrFail($id);
        $embed->is_active = ! $embed->is_active;
        $embed->save();

        return back();
    }

    /**
     * Public view-tracking endpoint. The JS tag fires this via
     * `navigator.sendBeacon` once per embed render.
     *
     *   POST /track/orbit/banner/{banner_id}/view
     *
     * Routed by banner_id (what the JS tag knows) and resolved to
     * the orbit_embed row here. CSRF-exempt + throttled in routes.
     * Always returns 204 — silent no-op if the embed was removed
     * but a cached tag is still firing.
     */
    public function trackView(Request $request, $bannerId)
    {
        return $this->logEvent($request, $bannerId, 'view');
    }

    public function trackClick(Request $request, $bannerId)
    {
        return $this->logEvent($request, $bannerId, 'click');
    }

    private function logEvent(Request $request, $bannerId, string $type)
    {
        $embed = OrbitEmbed::where('banner_id', $bannerId)->first();

        if ($embed && $embed->is_active) {
            OrbitEvent::create([
                'orbit_embed_id' => $embed->id,
                'type'           => $type,
                'referrer'       => mb_substr((string) $request->headers->get('referer', ''), 0, 1024) ?: null,
                'ip'             => $request->ip(),
                'user_agent'     => mb_substr((string) $request->userAgent(), 0, 1024) ?: null,
            ]);
        }

        return response('', 204)
            ->header('Access-Control-Allow-Origin', '*');
    }

    /**
     * Iframe wrapper used by the Orbit JS tag. Streams the banner's
     * index.html with two injections:
     *   1. <base href> so relative assets keep resolving against the
     *      banner's extracted directory.
     *   2. A capture-phase click listener that beacons every click to
     *      the click-tracking endpoint.
     *
     * Only the JS tag's iframe goes through this route — the preview
     * page still loads /uploads/.../index.html directly, so preview
     * views never count toward Orbit metrics.
     */
    public function serveBanner($bannerId)
    {
        $banner = newBanner::find($bannerId);
        if (! $banner || ! $banner->path) {
            abort(404);
        }

        // No active Orbit embed → return a blank page so the iframe
        // shows nothing rather than the banner. We still 200 so the
        // browser doesn't draw its default error UI inside the frame.
        $embed = OrbitEmbed::where('banner_id', $banner->id)->first();
        if (! $embed || ! $embed->is_active) {
            return response('<!doctype html><html><head><meta charset="utf-8"></head><body></body></html>', 200)
                ->header('Content-Type', 'text/html; charset=utf-8')
                ->header('Cache-Control', 'no-store');
        }

        $relativePath = rtrim($banner->path, '/');
        $indexPath = public_path($relativePath . '/index.html');
        if (! is_file($indexPath)) {
            abort(404);
        }

        $html = (string) file_get_contents($indexPath);
        $baseHref = '/' . $relativePath . '/';
        $clickUrl = route('orbit.track.click', ['id' => $banner->id]);

        $inject = '<base href="' . htmlspecialchars($baseHref, ENT_QUOTES) . '">'
            . '<script>(function(){var u=' . json_encode($clickUrl, JSON_UNESCAPED_SLASHES) . ';'
            . 'document.addEventListener("click",function(){'
            . 'try{if(navigator.sendBeacon){navigator.sendBeacon(u);}'
            . 'else{fetch(u,{method:"POST",mode:"no-cors",keepalive:true});}}catch(e){}'
            . '},true);})();</script>';

        if (preg_match('/<head[^>]*>/i', $html)) {
            $html = preg_replace('/<head[^>]*>/i', '$0' . $inject, $html, 1);
        } else {
            $html = $inject . $html;
        }

        return response($html, 200)
            ->header('Content-Type', 'text/html; charset=utf-8')
            ->header('Cache-Control', 'no-store')
            ->header('X-Content-Type-Options', 'nosniff');
    }
}
