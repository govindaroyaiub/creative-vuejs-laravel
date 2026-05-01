<?php

namespace App\Http\Controllers;

use App\Models\BannerSize;
use App\Models\VideoSize;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

/**
 * Unified controller for managing both Banner sizes and Video sizes from one
 * page. Each request takes a `type` field (`banner` | `video`) to know which
 * underlying model to operate on. We deliberately keep the two database
 * tables and models split — only the UI is merged.
 */
class CreativeSizeController extends Controller
{
    private const TYPE_BANNER = 'banner';
    private const TYPE_VIDEO = 'video';

    /**
     * Resolve the requested `type` and validate it against the allowed values.
     */
    private function resolveType(Request $request): string
    {
        $type = $request->input('type', self::TYPE_BANNER);
        return $type === self::TYPE_VIDEO ? self::TYPE_VIDEO : self::TYPE_BANNER;
    }

    public function index(Request $request)
    {
        $type = $this->resolveType($request);
        $search = $request->input('search');

        if ($type === self::TYPE_VIDEO) {
            $query = VideoSize::query();
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('width', 'like', "%{$search}%")
                        ->orWhere('height', 'like', "%{$search}%");
                });
            }
            $sizes = $query->orderBy('name')->orderBy('width')->orderBy('height')
                ->paginate(15)->withQueryString();
        } else {
            $query = BannerSize::query();
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('width', 'like', "%{$search}%")
                        ->orWhere('height', 'like', "%{$search}%")
                        ->orWhereRaw("CONCAT(width, 'x', height) LIKE ?", ["%{$search}%"]);
                });
            }
            $sizes = $query->orderBy('width')->orderBy('height')
                ->paginate(15)->withQueryString();
        }

        return Inertia::render('CreativeSizes/Index', [
            'type' => $type,
            'sizes' => $sizes,
            'search' => $search,
            'counts' => [
                'banner' => BannerSize::count(),
                'video' => VideoSize::count(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $type = $this->resolveType($request);

        if ($type === self::TYPE_VIDEO) {
            $validated = $request->validate([
                'width' => 'required|integer|min:1',
                'height' => 'required|integer|min:1',
            ]);
            // `name` is auto-derived from dimensions — the DB column remains
            // but no longer accepts user input (it's always WxH).
            $validated['name'] = "{$validated['width']}x{$validated['height']}";

            $exists = VideoSize::where('width', $validated['width'])
                ->where('height', $validated['height'])
                ->exists();

            if ($exists) {
                return redirect()->route('creative-sizes-index', ['type' => 'video'])
                    ->with('error', 'Sorry! This video size already exists.');
            }

            VideoSize::create($validated);
            return redirect()->route('creative-sizes-index', ['type' => 'video'])
                ->with('success', 'Video size added successfully.');
        }

        $validated = $request->validate([
            'width' => 'required|integer|min:1',
            'height' => 'required|integer|min:1',
        ]);

        $exists = BannerSize::where('width', $validated['width'])
            ->where('height', $validated['height'])
            ->exists();

        if ($exists) {
            return redirect()->route('creative-sizes-index')
                ->with('error', 'Sorry! This banner size already exists.');
        }

        BannerSize::create($validated);
        return redirect()->route('creative-sizes-index')
            ->with('success', 'Banner size added successfully.');
    }

    public function update(Request $request, $id)
    {
        $type = $this->resolveType($request);

        if ($type === self::TYPE_VIDEO) {
            $videoSize = VideoSize::findOrFail($id);
            $validated = $request->validate([
                'width' => 'required|integer|min:1',
                'height' => 'required|integer|min:1',
            ]);
            // Keep `name` synced with the dimensions on every update.
            $validated['name'] = "{$validated['width']}x{$validated['height']}";

            $exists = VideoSize::where('width', $validated['width'])
                ->where('height', $validated['height'])
                ->where('id', '!=', $id)
                ->exists();

            if ($exists) {
                return redirect()->route('creative-sizes-index', ['type' => 'video'])
                    ->with('error', 'Sorry! This video size already exists.');
            }

            $videoSize->update($validated);
            return redirect()->route('creative-sizes-index', ['type' => 'video'])
                ->with('success', 'Video size updated successfully.');
        }

        $bannerSize = BannerSize::findOrFail($id);
        $request->validate([
            'width' => [
                'required',
                'integer',
                Rule::unique('banner_sizes')
                    ->where(fn($q) => $q->where('height', $request->height))
                    ->ignore($bannerSize->id),
            ],
            'height' => 'required|integer|min:1',
        ], ['width.unique' => 'Sorry, this width and height combo already exists.']);

        $bannerSize->update([
            'width' => $request->width,
            'height' => $request->height,
        ]);

        return redirect()->route('creative-sizes-index')
            ->with('success', 'Banner size updated successfully.');
    }

    public function destroy(Request $request, $id)
    {
        $type = $this->resolveType($request);

        if ($type === self::TYPE_VIDEO) {
            VideoSize::findOrFail($id)->delete();
            return redirect()->route('creative-sizes-index', ['type' => 'video'])
                ->with('success', 'Video size deleted.');
        }

        BannerSize::findOrFail($id)->delete();
        return redirect()->route('creative-sizes-index')
            ->with('success', 'Banner size deleted.');
    }
}
