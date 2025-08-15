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
    public function create() {}

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
            'category_name' => 'nullable|string|max:255',
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

        DB::transaction(function () use ($request) {
            // 1. Create the Preview

            $preview = newPreview::create([
                'name' => $request->name,
                'client_id' => $request->client_id,
                'requires_login' => $request->requires_login,
                'team_members' => $request->team_ids,
                'color_palette_id' => $request->color_palette_id,
                'uploader_id' => Auth::id(),
            ]);

            if ($request->type === 'Banner') {
                // 2. Create Version
                $category = $preview->categories()->create([
                    'name' => $request->input('category_name', 'Banners'),
                    'type' => 'banner',
                    'is_active' => true,
                ]);

                // 3. Create SubVersion
                $feedback = $category->feedbacks()->create([
                    'name' => $request->input('feedback_name', 'Feedback'),
                    'description' => $request->input('feedback_description'),
                    'is_active' => $request->boolean('feedback_active', true),
                ]);

                if ($request->has('banner_sets')) {
                    foreach ((array) $request->input('banner_sets', []) as $setIndex => $bannerSet) {
                        // 3a) Create FeedbackSet for each Set
                        $feedbackSet = $feedback->feedbackSets()->create([
                            'name' => $bannerSet['name'],
                        ]);

                        // 3b) For each Version under this Set
                        foreach ((array) ($bannerSet['versions'] ?? []) as $vIndex => $versionData) {
                            $version = $feedbackSet->versions()->create([
                                'name' => $versionData['name'],
                            ]);

                            // 3c) For each Banner (ZIP) under this Version
                            foreach ((array) ($versionData['banners'] ?? []) as $bIndex => $bannerData) {
                                // Files in nested arrays must be fetched with file()
                                $file = $request->file("banner_sets.$setIndex.versions.$vIndex.banners.$bIndex.file");
                                if (!$file) {
                                    continue;
                                }

                                $sizeId = $bannerData['size_id'] ?? null;
                                $size = BannerSize::findOrFail($sizeId);
                                $dimension = $size->width . 'x' . $size->height;

                                $uploadPath = public_path('uploads/banners');
                                if (!is_dir($uploadPath)) {
                                    mkdir($uploadPath, 0755, true);
                                }

                                $zipName = $request->name . '_' . $dimension . '_' . Str::uuid() . '.zip';

                                // File size display
                                $sizeInBytes = $file->getSize();
                                $fileSize = $sizeInBytes >= 1048576
                                    ? round($sizeInBytes / 1048576, 2) . ' MB'
                                    : round($sizeInBytes / 1024, 2) . ' KB';

                                // Move
                                $file->move($uploadPath, $zipName);

                                // Unzip to folder named after zip (without extension)
                                $zip = new ZipArchive;
                                $extractedFolder = $uploadPath . '/' . pathinfo($zipName, PATHINFO_FILENAME);
                                if (!is_dir($extractedFolder)) {
                                    mkdir($extractedFolder, 0755, true);
                                }
                                if ($zip->open($uploadPath . '/' . $zipName) === true) {
                                    $zip->extractTo($extractedFolder);
                                    $zip->close();
                                    @unlink($uploadPath . '/' . $zipName);
                                } else {
                                    throw new \RuntimeException("Failed to extract: $zipName");
                                }

                                // Create Banner for this Version
                                newBanner::create([
                                    'version_id' => $version->id,
                                    'name'       => $preview->name, // or any naming you prefer
                                    'path'       => 'uploads/banners/' . basename($extractedFolder),
                                    'size_id'    => $sizeId,
                                    'file_size'  => $fileSize,
                                    'position'   => (int)($bannerData['position'] ?? $bIndex),
                                ]);
                            }
                        }
                    }
                }
            }
        });

        return redirect()->route('previews-index')->with('success', 'Preview created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $preview_id = $id;

        $preview = newPreview::with(['client.colorPalette', 'colorPalette', 'uploader'])->findOrFail($id);

        // ✅ Check if login is required
        if ($preview->requires_login && !Auth::check()) {
            // Store redirect path in session
            Session::put('preview_redirect_after_login', route('previews-show', $id));
            return Inertia::render('Previews/Login', [
                'preview_id' => $id,
            ]);
        }

        // Continue with preview rendering
        $color_palettes = ColorPalette::find($preview->color_palette_id);
        $client = Client::find($preview->client_id);
        $all_colors = ColorPalette::where('status', 1)->select('id', 'primary', 'tertiary')->get();

        $primary = $color_palettes->primary;
        $secondary = $color_palettes->secondary;
        $tertiary = $color_palettes->tertiary;
        $quaternary = $color_palettes->quaternary;

        $authUserClientName = Auth::check()
            ? (Client::find(Auth::user()->client_id)?->name ?? 'Unknown')
            : 'guest';

        return view('preview4', compact(
            'preview',
            'primary',
            'secondary',
            'tertiary',
            'quaternary',
            'client',
            'authUserClientName',
            'preview_id',
            'all_colors'
        ));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(newPreview $newPreview)
    {
        $teamIds = $newPreview->team_members ?? []; // already an array if casted

        $teamUsers = User::whereIn('id', $teamIds)->get(['id', 'name']);

        return Inertia::render('Previews/Edit', [
            'preview' => $newPreview,
            'clients' => Client::all(['id', 'name']),
            'users' => User::all(['id', 'name']),
            'palettes' => ColorPalette::all(['id', 'name']),
            'teamUsers' => $teamUsers,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, newPreview $newPreview)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'client_id' => ['required', 'exists:clients,id'],
            'team_ids' => ['required', 'array'],
            'team_ids.*' => ['exists:users,id'],
            'color_palette_id' => ['nullable', 'exists:color_palettes,id'],
            'requires_login' => ['required', 'boolean'],
            'show_planetnine_logo' => ['required', 'boolean'],
            'show_sidebar_logo' => ['required', 'boolean'],
            'show_footer' => ['required', 'boolean'],
        ]);

        $newPreview->update([
            'name' => $validated['name'],
            'client_id' => $validated['client_id'],
            'team_members' => $validated['team_ids'], // will be stored as JSON
            'color_palette_id' => $validated['color_palette_id'],
            'requires_login' => $validated['requires_login'],
            'show_planetnine_logo' => $validated['show_planetnine_logo'],
            'show_sidebar_logo' => $validated['show_sidebar_logo'],
            'show_footer' => $validated['show_footer'],
        ]);

        return redirect()
            ->route('previews-edit', $newPreview->id)
            ->with('success', 'Preview updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(newPreview $newPreview)
    {
        DB::transaction(function () use ($newPreview) {
            $preview = newPreview::findorFail($newPreview->id);

            foreach ($preview->categories as $category) {
                foreach ($category->feedbacks as $feedback) {
                    foreach ($feedback->feedbackSets as $set) {
                        foreach ($set->versions as $version) {
                            foreach ($version->banners as $banner) {
                                $this->deletePath($banner->path);
                                $banner->delete();
                            }
                            // foreach ($version->videos as $video) {
                            //     $this->deletePath($video->path);
                            //     if ($video->companion_banner_path) {
                            //         $this->deletePath($video->companion_banner_path);
                            //     }
                            //     $video->delete();
                            // }
                            // foreach ($version->socials as $social) {
                            //     $this->deletePath($social->path);
                            //     $social->delete();
                            // }
                            // foreach ($version->gifs as $gif) {
                            //     $this->deletePath($gif->path);
                            //     $gif->delete();
                            // }
                        }
                        $set->delete();
                    }
                    $feedback->delete();
                }
                $category->delete();
            }
            $preview->delete();
        });

        return redirect()->route('previews-index')->with('success', 'Preview and all related files deleted successfully.');
    }

    protected function deletePath($relativePath)
    {
        $fullPath = public_path($relativePath);

        if (File::exists($fullPath)) {
            if (File::isDirectory($fullPath)) {
                File::deleteDirectory($fullPath); // used for banner folders
            } elseif (File::isFile($fullPath)) {
                File::delete($fullPath); // used for single video/gif/social files
            }
        }
    }
}
