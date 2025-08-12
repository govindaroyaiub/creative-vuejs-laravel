<?php

namespace App\Http\Controllers;

use App\Models\newPreview;
use Illuminate\Http\Request;
use ZipArchive;
use Inertia\Inertia;
use getID3;
use App\Models\Client;
use App\Models\User;
use App\Models\BannerSize;
use App\Models\Version;
use App\Models\newCategory;
use App\Models\newFeedback;
use App\Models\newFeedbackSet;
use App\Models\newVersion;
use App\Models\newBanner;
use App\Models\ColorPalette;
use App\Models\VideoSize;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class NewPreviewController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = newPreview::with(['client', 'uploader', 'colorPalette', 'categories.feedbacks']);

        if ($search = $request->input('search')) {
            $query->where('name', 'like', "%{$search}%")
                ->orWhereHas('client', fn($q) => $q->where('name', 'like', "%{$search}%"))
                ->orWhereHas('uploader', fn($q) => $q->where('name', 'like', "%{$search}%"));
        }

        $previews = $query->orderBy('created_at', 'desc')->paginate(10)->withQueryString()->through(function ($preview) {
            $preview->team_users = User::whereIn('id', $preview->team_members)->get(['id', 'name']);
            return $preview;
        });

        return Inertia::render('Previews/Index', [
            'previews' => $previews,
            'search' => $search,
            'clients' => Client::orderBy('name')->get(['id', 'name', 'preview_url']),
            'users' => User::orderBy('name')->get(['id', 'name']),
            'colorPalettes' => ColorPalette::orderBy('name')->get(['id', 'name']),
            'bannerSizes' => BannerSize::orderBy('width')->orderBy('height')->get(['id', 'width', 'height'])->map(fn($s) => tap($s, fn($s) => $s->name = "{$s->width}x{$s->height}")),
            'videoSizes' => VideoSize::orderBy('name')->orderBy('width')->orderBy('height')->get(['id', 'name', 'width', 'height'])->map(fn($s) => tap($s, fn($s) => $s->name = "{$s->name}")),
            'auth' => ['user' => Auth::user()],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validates = $request->validate([
            'name' => 'required|string|max:255',
            'client_id' => 'required|exists:clients,id',
            'color_palette_id' => 'required|exists:color_palettes,id',
            'requires_login' => 'boolean',
            'show_planetnine_logo' => 'boolean',
            'show_sidebar_logo' => 'boolean',
            'show_footer' => 'boolean',
            'team_ids' => 'required|array',
            'team_ids.*' => 'exists:users,id',
            'type' => 'required|in:Banner,Video,Social,Gif',
            'version_name' => 'nullable|string|max:255',
            'version_description' => 'nullable|string',
            'subversion_name' => 'nullable|string|max:255',
            'subversion_active' => 'nullable|boolean',
            'banner_sets' => 'nullable|array',
            'banner_sets.*.name' => 'nullable|string|max:255',
            'banner_sets.*.versions' => 'nullable|array',
            'banner_sets.*.versions.*.name' => 'nullable|string|max:255',
            'banner_sets.*.versions.*.banners' => 'nullable|array',
            'banner_sets.*.versions.*.banners.*.size_id' => 'required_with:banners.*.file|exists:banner_sizes,id',
            'banner_sets.*.versions.*.banners.*.file' => 'required_with:banners|file|mimes:zip',
            'banner_sets.*.versions.*.banners.*.position' => 'required|integer',
        ]);

        dd($validates);
    }

    /**
     * Display the specified resource.
     */
    public function show(newPreview $newPreview)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(newPreview $newPreview)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, newPreview $newPreview)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(newPreview $newPreview)
    {
        //
    }
}
