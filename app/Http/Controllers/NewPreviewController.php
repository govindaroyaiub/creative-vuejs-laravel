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
use App\Models\newVideo;
use App\Models\newGif;
use App\Models\newSocial;
use Illuminate\Validation\Rule;

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
        ]);

        $preview = null;

        DB::transaction(function () use ($request, &$preview) {
            $preview = newPreview::create([
                'slug' => Str::uuid()->toString(),
                'name' => $request->name,
                'client_id' => $request->client_id,
                'requires_login' => $request->requires_login,
                'team_members' => $request->team_ids,
                'color_palette_id' => $request->color_palette_id,
                'uploader_id' => Auth::id(),
            ]);
        });

        // Redirect to bulkEdit with the new preview's ID
        return redirect()->route('previews.update.all', $preview->id)
            ->with('success', 'Preview created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show($slug)
    {
        $preview = newPreview::with(['client.colorPalette', 'colorPalette', 'uploader'])->where('slug', $slug)->firstOrFail();
        $id = $preview_id = $preview->id;

        // ✅ Check if login is required
        if ($preview->requires_login && !Auth::check()) {
            // Store redirect path in session
            Session::put('preview_redirect_after_login', route('previews-show', $slug));
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

        return view('preview5', compact(
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
                            foreach ($version->videos as $video) {
                                $this->deletePath($video->path);
                                if ($video->companion_banner_path) {
                                    $this->deletePath($video->companion_banner_path);
                                }
                                $video->delete();
                            }
                            foreach ($version->socials as $social) {
                                $this->deletePath($social->path);
                                $social->delete();
                            }
                            foreach ($version->gifs as $gif) {
                                $this->deletePath($gif->path);
                                $gif->delete();
                            }
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

    public function updatePreview($id)
    {
        $preview = newPreview::with([
            'categories.feedbacks.feedbackSets.versions.banners',
            'categories.feedbacks.feedbackSets.versions.videos',
            'categories.feedbacks.feedbackSets.versions.socials',
            'categories.feedbacks.feedbackSets.versions.gifs',
        ])->findOrFail($id);

        return Inertia::render('Previews/Update', [
            'preview' => $preview,
            'bannerSizes' => BannerSize::all(),
            'videoSizes' => VideoSize::all(),
            // Add other needed data here
        ]);
    }

    public function bulkEdit(Request $request, $id)
    {
        $validated = $request->validate([
            'preview_id' => 'required|exists:new_previews,id',
            'categories' => 'required|array|min:1',
            'categories.*.name' => 'required|string|max:255',
            'categories.*.type' => ['required', \Illuminate\Validation\Rule::in(['banner', 'video', 'social', 'gif'])],
            'categories.*.feedbacks' => 'required|array|min:1',
            'categories.*.feedbacks.*.name' => 'required|string|max:255',
            'categories.*.feedbacks.*.description' => 'required|string|max:1000',
            'categories.*.feedbacks.*.feedback_sets' => 'required|array|min:1',
            'categories.*.feedbacks.*.feedback_sets.*.name' => 'nullable|string|max:255',
            'categories.*.feedbacks.*.feedback_sets.*.versions' => 'required|array|min:1',
            'categories.*.feedbacks.*.feedback_sets.*.versions.*.name' => 'nullable|string|max:255',
            'categories.*.feedbacks.*.feedback_sets.*.versions.*.banners' => 'array',
            'categories.*.feedbacks.*.feedback_sets.*.versions.*.banners.*.id' => 'nullable|integer',
            'categories.*.feedbacks.*.feedback_sets.*.versions.*.banners.*.name' => 'nullable|string|max:255',
            'categories.*.feedbacks.*.feedback_sets.*.versions.*.banners.*.size_id' => 'required_if:categories.*.type,banner|exists:banner_sizes,id',
            'categories.*.feedbacks.*.feedback_sets.*.versions.*.banners.*.position' => 'required|integer',
            'categories.*.feedbacks.*.feedback_sets.*.versions.*.banners.*.file' => 'nullable|file|mimes:zip',
            // Add similar validation for video, gif, social when needed
        ]);

        $preview = newPreview::with([
            'categories.feedbacks.feedbackSets.versions.banners'
        ])->findOrFail($id);

        DB::transaction(function () use ($validated, $preview) {
            // 1. Handle deleted categories
            $existingCategories = $preview->categories->keyBy('id');
            $submittedCategoryNames = collect($validated['categories'])->pluck('name')->all();

            foreach ($existingCategories as $catId => $category) {
                if (!in_array($category->name, $submittedCategoryNames)) {
                    foreach ($category->feedbacks as $feedback) {
                        foreach ($feedback->feedbackSets as $set) {
                            foreach ($set->versions as $version) {
                                foreach ($version->banners as $banner) {
                                    $this->deletePath($banner->path);
                                    $banner->delete();
                                }
                                $version->delete();
                            }
                            $set->delete();
                        }
                        $feedback->delete();
                    }
                    $category->delete();
                }
            }

            // 2. Upsert categories
            foreach ($validated['categories'] as $catData) {
                $category = newCategory::firstOrCreate([
                    'preview_id' => $preview->id,
                    'name' => $catData['name'],
                ], [
                    'type' => $catData['type'],
                    'is_active' => true,
                ]);
                $category->update(['is_active' => true, 'type' => $catData['type']]);

                newCategory::where('preview_id', $preview->id)
                    ->where('id', '!=', $category->id)
                    ->update(['is_active' => false]);

                // 3. Handle deleted feedbacks
                $existingFeedbacks = $category->feedbacks->keyBy('id');
                $submittedFeedbackNames = collect($catData['feedbacks'])->pluck('name')->all();

                foreach ($existingFeedbacks as $fbId => $feedback) {
                    if (!in_array($feedback->name, $submittedFeedbackNames)) {
                        foreach ($feedback->feedbackSets as $set) {
                            foreach ($set->versions as $version) {
                                foreach ($version->banners as $banner) {
                                    $this->deletePath($banner->path);
                                    $banner->delete();
                                }
                                $version->delete();
                            }
                            $set->delete();
                        }
                        $feedback->delete();
                    }
                }

                // 4. Upsert feedbacks
                foreach ($catData['feedbacks'] as $fbData) {
                    $feedback = newFeedback::firstOrCreate([
                        'category_id' => $category->id,
                        'name' => $fbData['name'],
                    ], [
                        'description' => $fbData['description'],
                        'is_active' => true,
                    ]);
                    $feedback->update(['description' => $fbData['description'], 'is_active' => true]);

                    newFeedback::where('category_id', $category->id)
                        ->where('id', '!=', $feedback->id)
                        ->update(['is_active' => false]);

                    // 5. Handle deleted sets
                    $existingSets = $feedback->feedbackSets->keyBy('id');
                    $submittedSetNames = collect($fbData['feedback_sets'])->pluck('name')->all();

                    foreach ($existingSets as $setId => $set) {
                        if (!in_array($set->name, $submittedSetNames)) {
                            foreach ($set->versions as $version) {
                                foreach ($version->banners as $banner) {
                                    $this->deletePath($banner->path);
                                    $banner->delete();
                                }
                                $version->delete();
                            }
                            $set->delete();
                        }
                    }

                    // 6. Upsert sets
                    foreach ($fbData['feedback_sets'] as $setData) {
                        $set = newFeedbackSet::firstOrCreate([
                            'feedback_id' => $feedback->id,
                            'name' => $setData['name'] ?? '',
                        ]);
                        $set->update(['name' => $setData['name'] ?? '']);

                        // 7. Handle deleted versions
                        $existingVersions = $set->versions->keyBy('id');
                        $submittedVersionNames = collect($setData['versions'])->pluck('name')->all();

                        foreach ($existingVersions as $verId => $version) {
                            if (!in_array($version->name, $submittedVersionNames)) {
                                foreach ($version->banners as $banner) {
                                    $this->deletePath($banner->path);
                                    $banner->delete();
                                }
                                $version->delete();
                            }
                        }

                        // 8. Upsert versions
                        foreach ($setData['versions'] as $verData) {
                            $version = newVersion::firstOrCreate([
                                'feedback_set_id' => $set->id,
                                'name' => $verData['name'] ?? '',
                            ]);
                            $version->update(['name' => $verData['name'] ?? '']);

                            // 9. Handle deleted banners (with safe check)
                            $existingBanners = $version->banners->keyBy('id');
                            $submittedBannerIds = isset($verData['banners'])
                                ? collect($verData['banners'])->pluck('id')->filter()->all()
                                : [];
                            foreach ($existingBanners as $bannerId => $banner) {
                                if (!in_array($bannerId, $submittedBannerIds)) {
                                    $this->deletePath($banner->path);
                                    $banner->delete();
                                }
                            }

                            // 10. Upsert banners
                            if ($category->type === 'banner' && isset($verData['banners'])) {
                                foreach ($verData['banners'] as $bannerData) {
                                    if (isset($bannerData['id']) && $existingBanners->has($bannerData['id'])) {
                                        // Update existing banner
                                        $banner = $existingBanners[$bannerData['id']];
                                        $banner->update([
                                            'name' => $bannerData['name'] ?? $banner->name,
                                            'size_id' => $bannerData['size_id'],
                                            'position' => $bannerData['position'],
                                        ]);
                                        // If a new file is uploaded, replace the file and update path/size
                                        if (isset($bannerData['file']) && $bannerData['file'] instanceof \Illuminate\Http\UploadedFile) {
                                            $this->deletePath($banner->path);
                                            $previewName = str_replace(' ', '_', $preview->name);
                                            $uniqueSuffix = uniqid('_');
                                            $uploadDir = public_path("uploads/banners");
                                            if (!is_dir($uploadDir)) {
                                                mkdir($uploadDir, 0777, true);
                                            }
                                            $zipName = $previewName . $uniqueSuffix . '.zip';
                                            $zipPath = $uploadDir . '/' . $zipName;
                                            $bannerData['file']->move($uploadDir, $zipName);

                                            // Calculate zip size
                                            $zipSizeBytes = filesize($zipPath);
                                            $zipSize = $zipSizeBytes < 1048576
                                                ? round($zipSizeBytes / 1024, 2) . ' KB'
                                                : round($zipSizeBytes / 1048576, 2) . ' MB';

                                            // Extract zip
                                            $extractDir = $uploadDir . '/' . $previewName . $uniqueSuffix;
                                            if (!is_dir($extractDir)) {
                                                mkdir($extractDir, 0777, true);
                                            }
                                            $zip = new \ZipArchive;
                                            if ($zip->open($zipPath) === TRUE) {
                                                $zip->extractTo($extractDir);
                                                $zip->close();
                                                unlink($zipPath); // Delete zip after extraction
                                            }

                                            $banner->update([
                                                'path' => "uploads/banners/{$previewName}{$uniqueSuffix}/",
                                                'file_size' => $zipSize,
                                            ]);
                                        }
                                    } else {
                                        // Create new banner
                                        if (isset($bannerData['file']) && $bannerData['file'] instanceof \Illuminate\Http\UploadedFile) {
                                            $previewName = str_replace(' ', '_', $preview->name);
                                            $uniqueSuffix = uniqid('_');
                                            $uploadDir = public_path("uploads/banners");
                                            if (!is_dir($uploadDir)) {
                                                mkdir($uploadDir, 0777, true);
                                            }
                                            $zipName = $previewName . $uniqueSuffix . '.zip';
                                            $zipPath = $uploadDir . '/' . $zipName;
                                            $bannerData['file']->move($uploadDir, $zipName);

                                            // Calculate zip size
                                            $zipSizeBytes = filesize($zipPath);
                                            $zipSize = $zipSizeBytes < 1048576
                                                ? round($zipSizeBytes / 1024, 2) . ' KB'
                                                : round($zipSizeBytes / 1048576, 2) . ' MB';

                                            // Extract zip
                                            $extractDir = $uploadDir . '/' . $previewName . $uniqueSuffix;
                                            if (!is_dir($extractDir)) {
                                                mkdir($extractDir, 0777, true);
                                            }
                                            $zip = new \ZipArchive;
                                            if ($zip->open($zipPath) === TRUE) {
                                                $zip->extractTo($extractDir);
                                                $zip->close();
                                                unlink($zipPath); // Delete zip after extraction
                                            }

                                            newBanner::create([
                                                'version_id' => $version->id,
                                                'name' => $bannerData['name'] ?? ($previewName . $uniqueSuffix),
                                                'size_id' => $bannerData['size_id'],
                                                'position' => $bannerData['position'],
                                                'path' => "uploads/banners/{$previewName}{$uniqueSuffix}/",
                                                'file_size' => $zipSize,
                                            ]);
                                        }
                                    }
                                }
                            }

                            // TODO: Add similar granular logic for video, gif, social
                        }
                    }
                }
            }
        });

        return redirect()->route('previews.update.all', $preview->id)
            ->with('success', 'Bulk update successful.');
    }
}
