<?php

namespace App\Http\Controllers;

use ZipArchive;
use Inertia\Inertia;
use getID3;
use App\Models\Client;
use App\Models\User;
use App\Models\BannerSize;
use App\Models\Preview;
use App\Models\Version;
use App\Models\SubVersion;
use App\Models\FeedbackSet;
use App\Models\SubBanner;
use App\Models\SubVideo;
use App\Models\SubSocial;
use App\Models\SubGif;
use App\Models\ColorPalette;
use App\Models\VideoSize;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class PreviewController extends Controller
{
    public function show($id)
    {
        $preview_id = $id;

        $preview = Preview::with(['client.colorPalette', 'colorPalette', 'uploader'])->findOrFail($id);

        // ✅ Check if login is required
        if ($preview->requires_login && !Auth::check()) {
            // Store redirect path in session
            Session::put('preview_redirect_after_login', route('previews-show', $id));
            return Inertia::render('Previews/Login', [
                'preview_id' => $id,
            ]);
        }

        // Continue with preview rendering
        $versions = Version::where('preview_id', $id)->get();
        $subVersions = SubVersion::whereIn('version_id', $versions->pluck('id'))->get();
        $feedbackSets = FeedbackSet::whereIn('sub_version_id', $subVersions->pluck('id'))->get();
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

        return view('preview-main', compact(
            'preview',
            'versions',
            'subVersions',
            'feedbackSets',
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

    public function index(Request $request)
    {
        $query = Preview::with(['client', 'uploader', 'colorPalette', 'versions.subVersions']);

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

    public function create()
    {
        return Inertia::render('Previews/Create', [
            'clients' => Client::orderBy('name')->get(['id', 'name']),
            'users' => User::orderBy('name')->get(['id', 'name']),
            'colorPalettes' => ColorPalette::orderBy('name')->get(['id', 'name']),
            'auth' => ['user' => Auth::user()],
        ]);
    }

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
            'banner_sets.*.name' => 'required|string|max:255',
            'banner_sets.*.banners' => 'required|array',
            'banner_sets.*.banners.*.file' => 'required_with:banners|file|mimes:zip',
            'banner_sets.*.banners.*.size_id' => 'required_with:banners.*.file|exists:banner_sizes,id',
            'banner_sets.*.banners.*.position' => 'required|integer',
            'socials' => 'nullable|array',
            'socials.*.file' => 'required_with:socials|file|mimes:jpg,jpeg,png',
            'socials.*.name' => 'required_with:socials|string|max:255',
            'socials.*.position' => 'required_with:socials|integer',
            'videos' => 'nullable|array',
            'videos.*.name' => 'required|string|max:255',
            'videos.*.codec' => 'required|string|max:255',
            'videos.*.fps' => 'required|string|max:255',
            'videos.*.size_id' => 'required|exists:video_sizes,id',
            'videos.*.path' => 'required|file|mimetypes:video/mp4,video/quicktime,video/x-msvideo,video/x-matroska,video/webm',
            'videos.*.companion_banner_path' => 'nullable|file|mimes:jpg,jpeg,png,gif',
            'gifs' => 'nullable|array',
            'gifs.*.file' => 'required_with:gifs|file|mimes:gif',
            'gifs.*.sizes' => 'required_with:gifs|array|size:1',
            'gifs.*.sizes.0' => 'required_with:gifs|exists:banner_sizes,id',
            'gifs.*.position' => 'nullable|integer',
        ]);

        DB::transaction(function () use ($request) {
            // 1. Create the Preview

            $preview = Preview::create([
                'name' => $request->name,
                'client_id' => $request->client_id,
                'requires_login' => $request->requires_login,
                'team_members' => $request->team_ids,
                'color_palette_id' => $request->color_palette_id,
                'uploader_id' => Auth::id(),
            ]);

            if ($request->type === 'Banner') {
                // 2. Create Version
                $version = $preview->versions()->create([
                    'name' => $request->version_name,
                    'type' => 'banner',
                    'is_active' => true,
                ]);

                // 3. Create SubVersion
                $subVersion = $version->subVersions()->create([
                    'name' => $request->subversion_name,
                    'description' => $request->version_description,
                    'is_active' => $request->boolean('subversion_active', true),
                ]);

                if (isset($request->banner_sets)) {
                    foreach ($request->banner_sets as $setIndex => $bannerSet) {
                        $feedbackSet = FeedbackSet::create([
                            'sub_version_id' => $subVersion->id,
                            'name' => $bannerSet['name'] ?? "Set " . ($setIndex + 1),
                        ]);

                        // Now handle banners for this set
                        if (isset($bannerSet['banners'])) {
                            foreach ($bannerSet['banners'] as $index => $banner) {
                                $file = $banner['file'];
                                $sizeId = $banner['size_id'];
                                $position = $banner['position'];
                                $size = BannerSize::findOrFail($sizeId);
                                $dimension = $size->width . 'x' . $size->height;

                                $zipName = $request->name . '_' . $dimension . '_' . Str::uuid() . '.zip';
                                $uploadPath = public_path('uploads/banners');

                                // Ensure directory exists
                                if (!is_dir($uploadPath)) {
                                    mkdir($uploadPath, 0755, true);
                                }

                                // Get size BEFORE move (in KB)
                                $sizeInBytes = $file->getSize();

                                if ($sizeInBytes >= 1048576) {
                                    // 1 MB = 1024 * 1024 = 1048576 bytes
                                    $fileSize = round($sizeInBytes / 1048576, 2) . ' MB';
                                } else {
                                    $fileSize = round($sizeInBytes / 1024, 2) . ' KB';
                                }

                                // Move zip
                                $file->move($uploadPath, $zipName);

                                // Unzip
                                $zip = new ZipArchive;
                                $extractedFolder = $uploadPath . '/' . pathinfo($zipName, PATHINFO_FILENAME);
                                if (!is_dir($extractedFolder)) {
                                    mkdir($extractedFolder, 0755, true);
                                }

                                if ($zip->open($uploadPath . '/' . $zipName) === true) {
                                    $zip->extractTo($extractedFolder);
                                    $zip->close();
                                    unlink($uploadPath . '/' . $zipName); // ✅ Delete original ZIP
                                } else {
                                    throw new \Exception("Failed to extract: $zipName");
                                }

                                // Save SubBanner with feedback_set_id
                                SubBanner::create([
                                    'sub_version_id' => $subVersion->id,
                                    'feedback_set_id' => $feedbackSet->id, // 🔥 Link to banner set
                                    'name' => $preview->name,
                                    'path' => 'uploads/banners/' . basename($extractedFolder),
                                    'size_id' => $sizeId,
                                    'file_size' => $fileSize,
                                    'position' => $position,
                                ]);
                            }
                        }
                    }
                }
            }
            if ($request->type === 'Social') {
                // 2. Create Version
                $version = $preview->versions()->create([
                    'name' => $request->version_name ?: 'Master',
                    'description' => $request->version_description ?: 'Master Started',
                    'type' => 'social',
                    'is_active' => true,
                ]);

                // 3. Create SubVersion
                $subVersion = $version->subVersions()->create([
                    'name' => $request->subversion_name ?: 'Version_1',
                    'is_active' => $request->boolean('subversion_active', true),
                ]);

                // 4. Handle Social image uploads
                $uploadPath = public_path('uploads/socials');
                if (!is_dir($uploadPath)) {
                    mkdir($uploadPath, 0755, true);
                }

                foreach ($request->socials as $index => $social) {
                    $file = $social['file'];
                    $name = $social['name'];
                    $position = $social['position'];

                    // Generate a unique filename
                    $ext = $file->getClientOriginalExtension();
                    $filename = $name . '_' . Str::uuid() . '.' . $ext;

                    // Move the file
                    $file->move($uploadPath, $filename);

                    // Save SubSocial
                    SubSocial::create([
                        'sub_version_id' => $subVersion->id,
                        'name' => $name,
                        'path' => 'uploads/socials/' . $filename,
                        'position' => $position,
                        // 'social_id' => null, // Set if you have a social_id to link
                    ]);
                }
            }
            if ($request->type === 'Video') {
                // 2. Create Version
                $version = $preview->versions()->create([
                    'name' => $request->version_name ?: 'Master',
                    'description' => $request->version_description ?: 'Master Started',
                    'type' => 'video',
                    'is_active' => true,
                ]);

                // 3. Create SubVersion
                $subVersion = $version->subVersions()->create([
                    'name' => $request->subversion_name ?: 'Version_1',
                    'is_active' => $request->boolean('subversion_active', true),
                ]);

                $uploadPath = public_path('uploads/videos');
                if (!is_dir($uploadPath)) {
                    mkdir($uploadPath, 0755, true);
                }

                foreach ($request->videos as $index => $video) {
                    $file = $video['path'];
                    $name = $video['name'];
                    $sizeId = $video['size_id'];
                    $position = $video['position'] ?? $index;

                    $ext = $file->getClientOriginalExtension();
                    $filename = $name . '_' . \Str::uuid() . '.' . $ext;
                    $file->move($uploadPath, $filename);
                    $videoPath = $uploadPath . '/' . $filename;

                    // --- getID3 metadata extraction ---
                    $getID3 = new \getID3;
                    $info = $getID3->analyze($videoPath);

                    $codec = $video['codec'] ?? null;
                    $width = $info['video']['resolution_x'] ?? null;
                    $height = $info['video']['resolution_y'] ?? null;
                    $aspect_ratio = $this->getAspectRatioString($width, $height);
                    $fps = $video['fps'] ?? null;
                    $file_size_bytes = filesize($videoPath);
                    if ($file_size_bytes >= 1048576) {
                        // 1 MB = 1024 * 1024 = 1048576 bytes
                        $file_size = round($file_size_bytes / 1048576, 2) . ' MB';
                    } else {
                        $file_size = round($file_size_bytes / 1024, 2) . ' KB';
                    }

                    // Handle companion banner if present
                    $companionBannerPath = null;
                    if (!empty($video['companion_banner_path'])) {
                        $bannerFile = $video['companion_banner_path'];
                        $bannerExt = $bannerFile->getClientOriginalExtension();
                        $bannerFilename = $name . '_companion_' . \Str::uuid() . '.' . $bannerExt;
                        $bannerUploadPath = public_path('uploads/videos/companions');
                        if (!is_dir($bannerUploadPath)) {
                            mkdir($bannerUploadPath, 0755, true);
                        }
                        $bannerFile->move($bannerUploadPath, $bannerFilename);
                        $companionBannerPath = 'uploads/videos/companions/' . $bannerFilename;
                    }

                    // Save SubVideo
                    SubVideo::create([
                        'sub_version_id' => $subVersion->id,
                        'name' => $name,
                        'path' => 'uploads/videos/' . $filename,
                        'size_id' => $sizeId,
                        'codec' => $codec,
                        'aspect_ratio' => $aspect_ratio,
                        'fps' => $fps,
                        'file_size' => $file_size,
                        'companion_banner_path' => $companionBannerPath,
                        'position' => $position,
                    ]);
                }
            }
            if ($request->type === 'Gif') {
                // 1. Create Version
                $version = $preview->versions()->create([
                    'name' => $request->version_name ?: 'Master',
                    'description' => $request->version_description ?: 'Master Started',
                    'type' => 'gif',
                    'is_active' => true,
                ]);

                // 2. Create SubVersion
                $subVersion = $version->subVersions()->create([
                    'name' => $request->subversion_name ?: 'Version_1',
                    'is_active' => $request->boolean('subversion_active', true),
                ]);

                // 3. Handle GIF uploads
                $uploadPath = public_path('uploads/gifs');
                if (!is_dir($uploadPath)) {
                    mkdir($uploadPath, 0755, true);
                }

                if (is_array($request->gifs)) {
                    foreach ($request->gifs as $index => $gif) {
                        // Defensive: skip if file or sizes missing
                        if (empty($gif['file']) || empty($gif['sizes'][0])) {
                            continue;
                        }

                        $file = $gif['file'];
                        $sizeId = $gif['sizes'][0];
                        $position = $index;

                        // Generate a unique filename
                        $ext = $file->getClientOriginalExtension();
                        $filename = 'gif_' . \Str::uuid() . '.' . $ext;

                        // Get file size BEFORE move
                        $sizeInBytes = $file->getSize();
                        $fileSize = $sizeInBytes >= 1048576
                            ? round($sizeInBytes / 1048576, 2) . ' MB'
                            : round($sizeInBytes / 1024, 2) . ' KB';

                        // Move the file
                        $file->move($uploadPath, $filename);

                        // Save SubGif
                        \App\Models\SubGif::create([
                            'sub_version_id' => $subVersion->id,
                            'name' => $filename,
                            'path' => 'uploads/gifs/' . $filename,
                            'size_id' => $sizeId,
                            'file_size' => $fileSize,
                            'position' => $position,
                        ]);
                    }
                }
            }
        });

        return redirect()->route('previews-index')->with('success', 'Preview created successfully.');
    }

    protected function getAspectRatioString($width, $height)
    {
        if (!$width || !$height) return null;

        // Calculate GCD
        $gcd = function ($a, $b) use (&$gcd) {
            return ($b == 0) ? $a : $gcd($b, $a % $b);
        };

        $divisor = $gcd($width, $height);
        $w = $width / $divisor;
        $h = $height / $divisor;

        return "{$w}:{$h}";
    }

    public function edit(Preview $preview)
    {
        $teamIds = $preview->team_members ?? []; // already an array if casted

        $teamUsers = User::whereIn('id', $teamIds)->get(['id', 'name']);

        return Inertia::render('Previews/Edit', [
            'preview' => $preview,
            'clients' => Client::all(['id', 'name']),
            'users' => User::all(['id', 'name']),
            'palettes' => ColorPalette::all(['id', 'name']),
            'teamUsers' => $teamUsers,
        ]);
    }

    public function update(Request $request, Preview $preview)
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

        $preview->update([
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
            ->route('previews-edit', $preview->id)
            ->with('success', 'Preview updated successfully.');
    }

    public function destroy($id)
    {
        DB::transaction(function () use ($id) {
            $preview = Preview::findOrFail($id);
            $versions = Version::where('preview_id', $id)->get();

            foreach ($versions as $version) {
                $subVersions = SubVersion::where('version_id', $version->id)->get();

                foreach ($subVersions as $subVersion) {
                    // 1. SubBanners (folders)
                    $banners = SubBanner::where('sub_version_id', $subVersion->id)->get();
                    foreach ($banners as $banner) {
                        $this->deletePath($banner->path);
                        $banner->delete();
                    }

                    // 2. SubVideos (files + companion)
                    $videos = SubVideo::where('sub_version_id', $subVersion->id)->get();
                    foreach ($videos as $video) {
                        $this->deletePath($video->path);
                        if ($video->companion_banner_path) {
                            $this->deletePath($video->companion_banner_path);
                        }
                        $video->delete();
                    }

                    // 3. SubGifs (files)
                    $gifs = SubGif::where('sub_version_id', $subVersion->id)->get();
                    foreach ($gifs as $gif) {
                        $this->deletePath($gif->path);
                        $gif->delete();
                    }

                    // 4. SubSocials (files)
                    $socials = SubSocial::where('sub_version_id', $subVersion->id)->get();
                    foreach ($socials as $social) {
                        $this->deletePath($social->path);
                        $social->delete();
                    }

                    // 5. Delete SubVersion
                    $subVersion->delete();
                }

                // 6. Delete Version
                $version->delete();
            }

            // 7. Finally delete the preview
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

    public function editVersion($id)
    {
        $version = Version::findOrFail($id);
        $preview = Preview::findOrFail($version->preview_id);

        return Inertia::render('Previews/Versions/Edit', [
            'version' => $version,
            'preview' => $preview,
        ]);
    }

    public function updateVersion($id, Request $request)
    {
        $version = Version::findOrFail($id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $version->update($validated);

        $preview = $version->preview; // load the associated preview for props

        return Inertia::render('Previews/Versions/Edit', [
            'version' => $version,
            'preview' => $preview,
            'flash' => ['success' => 'Version updated successfully!'],
        ]);
    }

    public function createBannerSubVersion($id)
    {
        $version = Version::with('preview')->findOrFail($id);
        $preview = Preview::findOrFail($version['preview_id']);
        $bannerSizes = BannerSize::orderBy('width')->orderBy('height')->get(['id', 'width', 'height'])->map(fn($s) => tap($s, fn($s) => $s->name = "{$s->width}x{$s->height}"));

        return Inertia::render('Previews/Versions/SubVersions/Banner/Create', [
            'version' => $version,
            'bannerSizes' => $bannerSizes,
            'preview' => $preview
        ]);
    }

    public function createVideoSubVersion($id)
    {
        $version = Version::with('preview')->findOrFail($id);
        $preview = Preview::findOrFail($version['preview_id']);
        $videoSizes = VideoSize::orderBy('name')->orderBy('width')->orderBy('height')->get(['id', 'name', 'width', 'height'])->map(fn($s) => tap($s, fn($s) => $s->name = "{$s->name}"));

        return Inertia::render('Previews/Versions/SubVersions/Video/Create', [
            'version' => $version,
            'videoSizes' => $videoSizes,
            'preview' => $preview
        ]);
    }

    public function storeVideoSubVersion($id, Request $request)
    {
        $versionId = $id;

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'videos' => ['required', 'array', 'min:1'],
            'videos.*.name' => ['required', 'string', 'max:255'],
            'videos.*.size_id' => ['required', 'exists:video_sizes,id'],
            'videos.*.codec' => ['required', 'string', 'max:255'],
            'videos.*.fps' => ['required', 'string', 'max:255'],
            'videos.*.path' => ['required', 'file', 'mimetypes:video/mp4,video/quicktime,video/x-msvideo,video/x-matroska,video/webm'],
            'videos.*.companion_banner_path' => ['nullable', 'file', 'mimes:jpg,jpeg,png,gif'],
        ]);

        $version = Version::findOrFail($versionId);
        $preview = $version->preview;

        // 1. Deactivate other subversions
        SubVersion::where('version_id', $versionId)->update(['is_active' => false]);

        // 2. Create the new active subversion
        $subVersion = SubVersion::create([
            'version_id' => $versionId,
            'name' => $request->name,
            'is_active' => true,
        ]);

        $uploadPath = public_path('uploads/videos');
        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0755, true);
        }

        foreach ($request->videos as $index => $video) {
            $file = $video['path'];
            $name = $video['name'];
            $sizeId = $video['size_id'];
            $codec = $video['codec'];
            $fps = $video['fps'];
            $position = $index; // Drag order

            // Save video file
            $ext = $file->getClientOriginalExtension();
            $filename = $name . '_' . \Str::uuid() . '.' . $ext;
            $file->move($uploadPath, $filename);
            $videoPath = $uploadPath . '/' . $filename;

            // Extract metadata
            $getID3 = new \getID3;
            $info = $getID3->analyze($videoPath);

            $width = $info['video']['resolution_x'] ?? null;
            $height = $info['video']['resolution_y'] ?? null;
            $aspect_ratio = $this->getAspectRatioString($width, $height);

            $file_size_bytes = filesize($videoPath);
            $file_size = $file_size_bytes >= 1048576
                ? round($file_size_bytes / 1048576, 2) . ' MB'
                : round($file_size_bytes / 1024, 2) . ' KB';

            // Handle companion banner if present
            $companionBannerPath = null;
            if (!empty($video['companion_banner_path'])) {
                $bannerFile = $video['companion_banner_path'];
                $bannerExt = $bannerFile->getClientOriginalExtension();
                $bannerFilename = $name . '_companion_' . \Str::uuid() . '.' . $bannerExt;
                $bannerUploadPath = public_path('uploads/videos/companions');
                if (!is_dir($bannerUploadPath)) {
                    mkdir($bannerUploadPath, 0755, true);
                }
                $bannerFile->move($bannerUploadPath, $bannerFilename);
                $companionBannerPath = 'uploads/videos/companions/' . $bannerFilename;
            }

            // Save SubVideo
            SubVideo::create([
                'sub_version_id' => $subVersion->id,
                'name' => $name,
                'path' => 'uploads/videos/' . $filename,
                'size_id' => $sizeId,
                'codec' => $codec,
                'aspect_ratio' => $aspect_ratio,
                'fps' => $fps,
                'file_size' => $file_size,
                'companion_banner_path' => $companionBannerPath,
                'position' => $position,
            ]);
        }

        return response()->json([
            'redirect_to' => url("/previews/show/{$version->preview_id}")
        ], 200);
    }

    public function storeBannerSubVersion(Request $request, $id)
    {
        $versionId = $id;
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'banners' => ['required', 'array'],
            'banners.*.file' => ['required', 'file', 'mimes:zip'],
            'banners.*.size_id' => ['required', 'exists:banner_sizes,id'],
            'banners.*.position' => ['required', 'integer'],
        ]);

        $version = Version::findOrFail($versionId);
        $preview = $version->preview;

        // 1. Deactivate other subversions
        SubVersion::where('version_id', $versionId)->update(['is_active' => false]);

        // 2. Create the new active subversion
        $subVersion = SubVersion::create([
            'version_id' => $versionId,
            'name' => $request->name,
            'is_active' => true,
        ]);

        // 3. Process and store each uploaded banner
        foreach ($request->banners as $index => $banner) {
            $file = $banner['file'];
            $sizeId = $banner['size_id'];
            $position = $banner['position'];
            $size = BannerSize::findOrFail($sizeId);
            $dimension = $size->width . 'x' . $size->height;

            $zipName = $request->name . '_' . $dimension . '_' . Str::uuid() . '.zip';
            $uploadPath = public_path('uploads/banners');

            // Ensure directory exists
            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }

            // Get readable file size
            $sizeInBytes = $file->getSize();
            $fileSize = $sizeInBytes >= 1048576
                ? round($sizeInBytes / 1048576, 2) . ' MB'
                : round($sizeInBytes / 1024, 2) . ' KB';

            // Move uploaded zip
            $file->move($uploadPath, $zipName);

            // Extract zip to folder
            $zip = new ZipArchive;
            $extractedFolder = $uploadPath . '/' . pathinfo($zipName, PATHINFO_FILENAME);

            if (!is_dir($extractedFolder)) {
                mkdir($extractedFolder, 0755, true);
            }

            if ($zip->open($uploadPath . '/' . $zipName) === true) {
                $zip->extractTo($extractedFolder);
                $zip->close();
                unlink($uploadPath . '/' . $zipName); // Remove zip after extraction
            } else {
                throw new \Exception("Failed to extract: $zipName");
            }

            // Save SubBanner record
            SubBanner::create([
                'sub_version_id' => $subVersion->id,
                'name' => $preview->name,
                'path' => 'uploads/banners/' . basename($extractedFolder),
                'size_id' => $sizeId,
                'file_size' => $fileSize,
                'position' => $position,
            ]);
        }

        return response()->json([
            'redirect_to' => url("/previews/show/{$version->preview_id}")
        ], 200);
    }

    public function editBannerSubVersion($id)
    {
        $subVersion = SubVersion::with(['version.preview', 'banners'])->findOrFail($id);

        $version = $subVersion->version;
        $preview = $version->preview;

        $bannerSizes = BannerSize::all(['id', 'width', 'height']);

        return Inertia::render('Previews/Versions/SubVersions/Banner/Edit', [
            'subVersion' => $subVersion,
            'version' => $version,
            'preview' => $preview,
            'bannerSizes' => $bannerSizes,
        ]);
    }

    public function updateBannerSubVersion(Request $request, $id)
    {
        $subVersion = SubVersion::with('version.preview', 'banners')->findOrFail($id);
        $version = $subVersion->version;
        $preview = $version->preview;

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'banners' => ['nullable', 'array'],
            'banners.*.file' => ['required_with:banners', 'file', 'mimes:zip'],
            'banners.*.size_id' => ['required_with:banners', 'exists:banner_sizes,id'],
            'banners.*.position' => ['required_with:banners', 'integer'],
        ]);

        // 1. Update name
        $subVersion->update([
            'name' => $request->name,
        ]);

        // 2. If banners uploaded, replace old ones
        if ($request->has('banners')) {
            // Delete old banners (including folders)
            foreach ($subVersion->banners as $old) {
                $folderPath = public_path($old->path);
                if (is_dir($folderPath)) {
                    \File::deleteDirectory($folderPath); // Delete extracted folder
                }
                $old->delete(); // Delete DB entry
            }

            // Re-upload new banners
            foreach ($request->banners as $index => $banner) {
                $file = $banner['file'];
                $sizeId = $banner['size_id'];
                $position = $banner['position'];

                $size = BannerSize::findOrFail($sizeId);
                $dimension = $size->width . 'x' . $size->height;
                $zipName = $request->name . '_' . $dimension . '_' . Str::uuid() . '.zip';
                $uploadPath = public_path('uploads/banners');

                if (!is_dir($uploadPath)) {
                    mkdir($uploadPath, 0755, true);
                }

                $sizeInBytes = $file->getSize();
                $fileSize = $sizeInBytes >= 1048576
                    ? round($sizeInBytes / 1048576, 2) . ' MB'
                    : round($sizeInBytes / 1024, 2) . ' KB';

                $file->move($uploadPath, $zipName);

                $zip = new ZipArchive;
                $extractedFolder = $uploadPath . '/' . pathinfo($zipName, PATHINFO_FILENAME);

                if (!is_dir($extractedFolder)) {
                    mkdir($extractedFolder, 0755, true);
                }

                if ($zip->open($uploadPath . '/' . $zipName) === true) {
                    $zip->extractTo($extractedFolder);
                    $zip->close();
                    unlink($uploadPath . '/' . $zipName);
                } else {
                    throw new \Exception("Failed to extract: $zipName");
                }

                SubBanner::create([
                    'sub_version_id' => $subVersion->id,
                    'name' => $preview->name,
                    'path' => 'uploads/banners/' . basename($extractedFolder),
                    'size_id' => $sizeId,
                    'file_size' => $fileSize,
                    'position' => $position,
                ]);
            }
        }

        return redirect()->route('edit-banner-sub-version', $subVersion->id)
            ->with('success', 'SubVersion updated successfully.');
    }

    public function deleteBannerSubVersion($id)
    {
        $subVersion = SubVersion::with(['banners', 'version.preview'])->findOrFail($id);
        $versionId = $subVersion->version_id;
        $previewId = $subVersion->version->preview_id;

        // 1. Delete banner folders and records
        foreach ($subVersion->banners as $banner) {
            $folderPath = public_path($banner->path);
            if (File::isDirectory($folderPath)) {
                File::deleteDirectory($folderPath);
            }
            $banner->delete();
        }

        // 2. Delete SubVersion
        $subVersion->delete();

        // 3. Set latest remaining subversion as active (if any exist)
        $latest = SubVersion::where('version_id', $versionId)->latest('id')->first();
        if ($latest) {
            $latest->update(['is_active' => true]);
        }

        return $data = [
            'version_id' => $versionId,
        ];
    }

    public function deleteVersion($id)
    {
        $version = Version::findOrFail($id);
        $previewId = $version->preview_id;

        $subVersions = SubVersion::where('version_id', $version->id)->get();

        foreach ($subVersions as $subVersion) {
            // Delete SubBanners
            $banners = SubBanner::where('sub_version_id', $subVersion->id)->get();
            foreach ($banners as $banner) {
                $folder = public_path($banner->path);
                if (File::isDirectory($folder)) {
                    File::deleteDirectory($folder);
                }
                $banner->delete();
            }

            // Delete SubVideos
            $videos = SubVideo::where('sub_version_id', $subVersion->id)->get();
            foreach ($videos as $video) {
                if (File::exists(public_path($video->path))) {
                    File::delete(public_path($video->path));
                }

                if ($video->companion_banner_path && File::exists(public_path($video->companion_banner_path))) {
                    File::delete(public_path($video->companion_banner_path));
                }

                $video->delete();
            }

            // Delete SubGifs
            $gifs = SubGif::where('sub_version_id', $subVersion->id)->get();
            foreach ($gifs as $gif) {
                if (File::exists(public_path($gif->path))) {
                    File::delete(public_path($gif->path));
                }
                $gif->delete();
            }

            // Delete SubSocials
            $socials = SubSocial::where('sub_version_id', $subVersion->id)->get();
            foreach ($socials as $social) {
                if (File::exists(public_path($social->path))) {
                    File::delete(public_path($social->path));
                }
                $social->delete();
            }

            $subVersion->delete();
        }

        $version->delete();

        // Activate last version
        $lastVersion = Version::where('preview_id', $previewId)->latest()->first();
        if ($lastVersion) {
            $lastVersion->is_active = true;
            $lastVersion->save();
        }

        return ['version_id' => $lastVersion?->id];
    }

    public function createVersion($id)
    {
        $preview = Preview::findOrFail($id);
        $bannerSizes = BannerSize::all(['id', 'width', 'height']);
        $videoSizes = VideoSize::orderBy('name')->orderBy('width')->orderBy('height')->get(['id', 'name', 'width', 'height']);

        return Inertia::render('Previews/Versions/Create', [
            'preview' => $preview,
            'bannerSizes' => $bannerSizes,
            'videoSizes' => $videoSizes, // <-- add this line
        ]);
    }

    public function storeVersion(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:banner,social,video,gif',
            'sub_version_name' => 'required|string|max:255',

            // Banner validation
            'banners' => 'required_if:type,banner|array|min:1',
            'banners.*.file' => 'required_if:type,banner|file|mimes:zip',
            'banners.*.size_id' => 'required_if:type,banner|exists:banner_sizes,id',
            'banners.*.position' => 'required_if:type,banner|integer',

            // Social validation
            'socials' => 'required_if:type,social|array|min:1',
            'socials.*.file' => 'required_if:type,social|file|mimes:jpg,jpeg,png',
            'socials.*.name' => 'required_if:type,social|string|max:255',
            'socials.*.position' => 'required_if:type,social|integer',

            // Video validation
            'videos' => 'required_if:type,video|array|min:1',
            'videos.*.name' => 'required_if:type,video|string|max:255',
            'videos.*.size_id' => 'required_if:type,video|exists:video_sizes,id',
            'videos.*.codec' => 'required_if:type,video|string|max:255',
            'videos.*.fps' => 'required_if:type,video|string|max:255',
            'videos.*.path' => 'required_if:type,video|file|mimetypes:video/mp4,video/quicktime,video/x-msvideo,video/x-matroska,video/webm',
            'videos.*.companion_banner_path' => 'nullable|file|mimes:jpg,png,gif',
            'videos.*.position' => 'required_if:type,video|integer',
            // GIF validation
            'gifs' => 'required_if:type,gif|array|min:1',
            'gifs.*.file' => 'required_if:type,gif|file|mimes:gif',
            'gifs.*.sizes' => 'required_if:type,gif|array|size:1',
            'gifs.*.sizes.0' => 'required_if:type,gif|exists:banner_sizes,id',
            'gifs.*.position' => 'required_if:type,gif|integer',
        ]);

        DB::transaction(function () use ($request, $id) {
            $preview = Preview::findOrFail($id);

            // 1. Deactivate existing versions under this preview
            Version::where('preview_id', $id)->update(['is_active' => false]);

            // 2. Create new version
            $version = Version::create([
                'preview_id' => $id,
                'name' => $request->name,
                'description' => $request->description,
                'type' => $request->type,
                'is_active' => true,
            ]);

            // 3. Create subversion
            $subVersion = $version->subVersions()->create([
                'name' => $request->sub_version_name,
                'is_active' => true,
            ]);

            // 4. Handle banners
            if ($request->type === 'banner') {
                foreach ($request->banners as $index => $banner) {
                    $file = $banner['file'];
                    $sizeId = $banner['size_id'];
                    $position = $banner['position'];

                    $size = BannerSize::findOrFail($sizeId);
                    $dimension = "{$size->width}x{$size->height}";
                    $uuid = Str::uuid();
                    $zipName = "{$request->name}_{$dimension}_{$uuid}.zip";
                    $uploadPath = public_path('uploads/banners');

                    if (!is_dir($uploadPath)) {
                        mkdir($uploadPath, 0755, true);
                    }

                    $fileSize = $file->getSize() >= 1048576
                        ? round($file->getSize() / 1048576, 2) . ' MB'
                        : round($file->getSize() / 1024, 2) . ' KB';

                    $file->move($uploadPath, $zipName);

                    $zip = new \ZipArchive;
                    $extractedFolder = $uploadPath . '/' . pathinfo($zipName, PATHINFO_FILENAME);

                    if (!is_dir($extractedFolder)) {
                        mkdir($extractedFolder, 0755, true);
                    }

                    if ($zip->open($uploadPath . '/' . $zipName) === true) {
                        $zip->extractTo($extractedFolder);
                        $zip->close();
                        unlink($uploadPath . '/' . $zipName); // Delete original ZIP
                    } else {
                        throw new \Exception("Failed to extract: $zipName");
                    }

                    SubBanner::create([
                        'sub_version_id' => $subVersion->id,
                        'name' => $preview->name,
                        'path' => 'uploads/banners/' . basename($extractedFolder),
                        'size_id' => $sizeId,
                        'file_size' => $fileSize,
                        'position' => $position,
                    ]);
                }
            }

            // 5. Handle socials
            if ($request->type === 'social') {
                $uploadPath = public_path('uploads/socials');
                if (!is_dir($uploadPath)) {
                    mkdir($uploadPath, 0755, true);
                }

                foreach ($request->socials as $index => $social) {
                    $file = $social['file'];
                    $name = $social['name'];
                    $position = $social['position'];

                    // Generate a unique filename
                    $ext = $file->getClientOriginalExtension();
                    $filename = $name . '_' . Str::uuid() . '.' . $ext;

                    // Move the file
                    $file->move($uploadPath, $filename);

                    // Save SubSocial
                    SubSocial::create([
                        'sub_version_id' => $subVersion->id,
                        'name' => $name,
                        'path' => 'uploads/socials/' . $filename,
                        'position' => $position,
                        // 'social_id' => null, // Set if you have a social_id to link
                    ]);
                }
            }
            if ($request->type === 'video') {
                $uploadPath = public_path('uploads/videos');
                if (!is_dir($uploadPath)) {
                    mkdir($uploadPath, 0755, true);
                }

                foreach ($request->videos as $index => $video) {
                    $file = $video['path'];
                    $name = $video['name'];
                    $sizeId = $video['size_id'];
                    $codec = $video['codec'];
                    $fps = $video['fps'];
                    $position = $index; // Drag order

                    // Save video file
                    $ext = $file->getClientOriginalExtension();
                    $filename = $name . '_' . \Str::uuid() . '.' . $ext;
                    $file->move($uploadPath, $filename);
                    $videoPath = $uploadPath . '/' . $filename;

                    // Extract metadata
                    $getID3 = new \getID3;
                    $info = $getID3->analyze($videoPath);

                    $width = $info['video']['resolution_x'] ?? null;
                    $height = $info['video']['resolution_y'] ?? null;
                    $aspect_ratio = app('App\Http\Controllers\PreviewController')->getAspectRatioString($width, $height);

                    $file_size_bytes = filesize($videoPath);
                    $file_size = $file_size_bytes >= 1048576
                        ? round($file_size_bytes / 1048576, 2) . ' MB'
                        : round($file_size_bytes / 1024, 2) . ' KB';

                    // Handle companion banner if present
                    $companionBannerPath = null;
                    if (!empty($video['companion_banner_path'])) {
                        $bannerFile = $video['companion_banner_path'];
                        $bannerExt = $bannerFile->getClientOriginalExtension();
                        $bannerFilename = $name . '_companion_' . \Str::uuid() . '.' . $bannerExt;
                        $bannerUploadPath = public_path('uploads/videos/companions');
                        if (!is_dir($bannerUploadPath)) {
                            mkdir($bannerUploadPath, 0755, true);
                        }
                        $bannerFile->move($bannerUploadPath, $bannerFilename);
                        $companionBannerPath = 'uploads/videos/companions/' . $bannerFilename;
                    }

                    // Save SubVideo
                    SubVideo::create([
                        'sub_version_id' => $subVersion->id,
                        'name' => $name,
                        'path' => 'uploads/videos/' . $filename,
                        'size_id' => $sizeId,
                        'codec' => $codec,
                        'aspect_ratio' => $aspect_ratio,
                        'fps' => $fps,
                        'file_size' => $file_size,
                        'companion_banner_path' => $companionBannerPath,
                        'position' => $position,
                    ]);
                }
            }
            if ($request->type === 'gif') {
                $uploadPath = public_path('uploads/gifs');
                if (!is_dir($uploadPath)) {
                    mkdir($uploadPath, 0755, true);
                }

                foreach ($request->gifs as $index => $gif) {
                    // Defensive: skip if file or sizes missing
                    if (empty($gif['file']) || empty($gif['sizes'][0])) {
                        continue;
                    }

                    $file = $gif['file'];
                    $sizeId = $gif['sizes'][0];
                    $position = $gif['position'] ?? $index;

                    // Generate a unique filename
                    $ext = $file->getClientOriginalExtension();
                    $filename = 'gif_' . \Str::uuid() . '.' . $ext;

                    // Get file size BEFORE move
                    $sizeInBytes = $file->getSize();
                    $fileSize = $sizeInBytes >= 1048576
                        ? round($sizeInBytes / 1048576, 2) . ' MB'
                        : round($sizeInBytes / 1024, 2) . ' KB';

                    // Move the file
                    $file->move($uploadPath, $filename);

                    // Save SubGif
                    SubGif::create([
                        'sub_version_id' => $subVersion->id,
                        'name' => $filename,
                        'path' => 'uploads/gifs/' . $filename,
                        'size_id' => $sizeId,
                        'file_size' => $fileSize,
                        'position' => $position,
                    ]);
                }
            }
        });

        return response()->json([
            'redirect_to' => route('previews-show', $id),
            'message' => 'Version created successfully.',
        ]);
    }

    public function singleBannerEdit($id)
    {
        // Get the subBanner by ID
        $subBanner = SubBanner::findOrFail($id);

        // Get all available banner sizes
        $bannerSizes = BannerSize::all(['id', 'width', 'height']);

        // Match the size label (e.g., "300x250") manually
        $selectedSize = $bannerSizes->firstWhere('id', $subBanner->size_id);
        $subBanner->size_label = $selectedSize ? $selectedSize->width . 'x' . $selectedSize->height : '';

        $subVersionId = $subBanner->sub_version_id;
        $subVersion = SubVersion::findOrFail($subVersionId);
        $version = Version::findOrFail($subVersion->version_id);
        $preview = Preview::findOrFail($version->preview_id);

        return Inertia::render('Previews/Versions/SubVersions/Banner/SingleEdit', [
            'subBanner' => $subBanner,
            'preview' => $preview,
            'bannerSizes' => $bannerSizes->map(function ($size) {
                return [
                    'id' => $size->id,
                    'label' => $size->width . 'x' . $size->height,
                ];
            }),
        ]);
    }

    public function singleBannerUpdate($id, Request $request)
    {
        $request->validate([
            'size_id' => 'required|exists:banner_sizes,id',
            'zip' => 'nullable|file|mimes:zip',
        ]);

        $subBanner = SubBanner::findOrFail($id);

        // 1. Remove old extracted folder
        $oldFolder = public_path($subBanner->path);
        if (is_dir($oldFolder)) {
            \File::deleteDirectory($oldFolder);
        }

        $updateData = [
            'size_id' => $request->size_id,
        ];

        // 2. If new ZIP uploaded, move and extract
        if ($request->hasFile('zip')) {
            $file = $request->file('zip');
            $size = BannerSize::findOrFail($request->size_id);
            $dimension = "{$size->width}x{$size->height}";
            $uuid = \Str::uuid();
            $zipName = "banner_{$dimension}_{$uuid}.zip";
            $uploadPath = public_path('uploads/banners');

            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }

            $fileSize = $file->getSize() >= 1048576
                ? round($file->getSize() / 1048576, 2) . ' MB'
                : round($file->getSize() / 1024, 2) . ' KB';

            $file->move($uploadPath, $zipName);

            $zip = new \ZipArchive;
            $extractedFolder = $uploadPath . '/' . pathinfo($zipName, PATHINFO_FILENAME);

            if (!is_dir($extractedFolder)) {
                mkdir($extractedFolder, 0755, true);
            }

            if ($zip->open($uploadPath . '/' . $zipName) === true) {
                $zip->extractTo($extractedFolder);
                $zip->close();
                unlink($uploadPath . '/' . $zipName); // Delete original ZIP
            } else {
                throw new \Exception("Failed to extract: $zipName");
            }

            // Update path and file_size
            $updateData['path'] = 'uploads/banners/' . basename($extractedFolder);
            $updateData['file_size'] = $fileSize;
        }

        // 4. Update SubBanner
        $subBanner->update($updateData);

        return redirect()->back()->with('success', 'Banner updated successfully.');
    }

    public function singleBannerDownload($id)
    {
        $subBanner = SubBanner::findOrFail($id);
        $folderPath = public_path($subBanner->path);

        if (!is_dir($folderPath)) {
            abort(404, 'Banner folder not found.');
        }

        // Create a temporary zip file
        $zipFileName = 'banner_' . basename($folderPath) . '_' . '.zip';
        $zipFilePath = public_path($zipFileName);

        $zip = new \ZipArchive;
        if ($zip->open($zipFilePath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === true) {
            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($folderPath),
                \RecursiveIteratorIterator::LEAVES_ONLY
            );

            foreach ($files as $name => $file) {
                if (!$file->isDir()) {
                    $filePath = $file->getRealPath();
                    $relativePath = substr($filePath, strlen($folderPath) + 1);
                    $zip->addFile($filePath, $relativePath);
                }
            }
            $zip->close();
        } else {
            abort(500, 'Could not create ZIP file.');
        }

        // Return the zip as a download and delete after send
        return response()->download($zipFilePath)->deleteFileAfterSend(true);
    }

    public function singleBannerDelete($id)
    {
        $subBanner = SubBanner::findOrFail($id);
        $folderPath = public_path($subBanner->path);

        // Delete the folder if it exists
        if (is_dir($folderPath)) {
            \File::deleteDirectory($folderPath);
        }

        // Delete the SubBanner record
        $subBanner->delete();

        return $data = [
            'subVersion_id' => $subBanner->sub_version_id
        ];
    }

    public function createSocialSubVersion($id)
    {
        $version = Version::with('preview')->findOrFail($id);
        $preview = $version->preview;

        return Inertia::render('Previews/Versions/SubVersions/Social/Create', [
            'version' => $version,
            'preview' => $preview,
        ]);
    }

    public function storeSocialSubVersion(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'socials' => 'required|array|min:1',
            'socials.*.file' => 'required|file|mimes:jpg,jpeg,png',
            'socials.*.name' => 'required|string|max:255',
            'socials.*.position' => 'required|integer',
        ]);

        $versionInfo = Version::findOrFail($id);

        DB::transaction(function () use ($request, $id) {
            $version = Version::findOrFail($id);
            $preview = $version->preview;

            // 1. Deactivate other subversions
            SubVersion::where('version_id', $id)->update(['is_active' => false]);

            // 2. Create new active subversion
            $subVersion = SubVersion::create([
                'version_id' => $id,
                'name' => $request->name,
                'is_active' => true,
            ]);

            // 3. Handle social image uploads
            $uploadPath = public_path('uploads/socials');
            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }

            foreach ($request->socials as $index => $social) {
                $file = $social['file'];
                $name = $social['name'];
                $position = $social['position'];

                // Generate a unique filename
                $ext = $file->getClientOriginalExtension();
                $filename = "{$name}_" . Str::uuid() . ".{$ext}";

                // Move the file
                $file->move($uploadPath, $filename);

                // Save SubSocial
                SubSocial::create([
                    'sub_version_id' => $subVersion->id,
                    'name' => $name,
                    'path' => "uploads/socials/{$filename}",
                    'position' => $position,
                    // 'social_id' => null, // Set if you have a social_id to link
                ]);
            }
        });

        return response()->json([
            'redirect_to' => route('previews-show', ['id' => $versionInfo->preview_id]),
        ], 200);
    }

    public function editSocialSubVersion($id)
    {
        // $id is sub_versions.id
        $subVersion = SubVersion::with(['version.preview'])->findOrFail($id);
        $version = $subVersion->version;
        $preview = $version->preview;

        // Get all socials for this subversion, ordered by position
        $socials = SubSocial::where('sub_version_id', $subVersion->id)
            ->orderBy('position')
            ->get(['id', 'name', 'path', 'position']);

        return Inertia::render('Previews/Versions/SubVersions/Social/Edit', [
            'subVersion' => $subVersion,
            'version' => $version,
            'preview' => $preview,
            'socials' => $socials,
        ]);
    }

    public function updateSocialSubVersion($id, Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'socials' => 'nullable|array',
            'socials.*.file' => 'required_with:socials|file|mimes:jpg,jpeg,png',
            'socials.*.name' => 'required_with:socials|string|max:255',
            'socials.*.position' => 'required_with:socials|integer',
        ]);

        $subVersion = SubVersion::with('version')->findOrFail($id);
        $version = $subVersion->version;
        $preview_id = $version->preview_id;

        DB::transaction(function () use ($request, $subVersion, $version) {
            // 2. Set all subversions for this version to inactive
            SubVersion::where('version_id', $version->id)->update(['is_active' => false]);

            // 3. Set this subversion to active and update name
            $subVersion->update([
                'name' => $request->name,
                'is_active' => true,
            ]);

            // 4. Remove all SubSocials and their files for this subversion
            $oldSocials = SubSocial::where('sub_version_id', $subVersion->id)->get();
            foreach ($oldSocials as $social) {
                $filePath = public_path($social->path);
                if (file_exists($filePath)) {
                    @unlink($filePath);
                }
                $social->delete();
            }

            // 5. Insert new SubSocials and files (if any)
            if ($request->has('socials')) {
                $uploadPath = public_path('uploads/socials');
                if (!is_dir($uploadPath)) {
                    mkdir($uploadPath, 0755, true);
                }

                foreach ($request->socials as $index => $social) {
                    $file = $social['file'];
                    $name = $social['name'];
                    $position = $social['position'];

                    $ext = $file->getClientOriginalExtension();
                    $filename = "{$name}_" . \Str::uuid() . ".{$ext}";
                    $file->move($uploadPath, $filename);

                    SubSocial::create([
                        'sub_version_id' => $subVersion->id,
                        'name' => $name,
                        'path' => "uploads/socials/{$filename}",
                        'position' => $position,
                    ]);
                }
            }
        });

        return response()->json([
            'message' => 'Social SubVersion updated successfully.',
        ]);
    }

    public function singleSocialEdit($id)
    {
        $subSocial = SubSocial::findOrFail($id);
        $subVersion = SubVersion::findOrFail($subSocial->sub_version_id);
        $version = Version::findOrFail($subVersion->version_id);
        $preview = Preview::findOrFail($version->preview_id);

        return Inertia::render('Previews/Versions/SubVersions/Social/SingleEdit', [
            'subSocial' => $subSocial,
            'subVersion' => $subVersion,
            'version' => $version,
            'preview' => $preview,
        ]);
    }

    public function singleSocialUpdate($id, Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:jpg,jpeg,png',
        ]);

        $subSocial = SubSocial::findOrFail($id);

        // Remove old file
        $oldPath = public_path($subSocial->path);
        if (file_exists($oldPath)) {
            @unlink($oldPath);
        }

        // Save new file
        $file = $request->file('file');
        $ext = $file->getClientOriginalExtension();
        $filename = $subSocial->name . '_' . \Str::uuid() . '.' . $ext;
        $uploadPath = public_path('uploads/socials');
        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0755, true);
        }
        $file->move($uploadPath, $filename);

        // Update DB
        $subSocial->update([
            'path' => 'uploads/socials/' . $filename,
        ]);

        return response()->json([
            'message' => 'Image updated successfully.',
        ]);
    }

    public function singleSocialDelete($id)
    {
        $subSocial = SubSocial::findOrFail($id);
        $filePath = public_path($subSocial->path);

        // Delete the file if it exists
        if (file_exists($filePath)) {
            @unlink($filePath);
        }

        // Delete the SubSocial record
        $subSocial->delete();

        return response()->json([
            'message' => 'Social image deleted successfully.',
            'subVersion_id' => $subSocial->sub_version_id,
        ]);
    }

    public function deleteVideoSubVersion($id)
    {
        $subVersion = SubVersion::findOrFail($id);
        $version = Version::findOrFail($subVersion->version_id);
        $preview = Preview::findOrFail($version->preview_id);

        DB::transaction(function () use ($subVersion) {
            // 1. Remove all videos and their files associated with this subversion
            $videos = SubVideo::where('sub_version_id', $subVersion->id)->get();

            foreach ($videos as $video) {
                // Delete video file
                if ($video->path && file_exists(public_path($video->path))) {
                    @unlink(public_path($video->path));
                }
                // Delete companion banner file if exists
                if ($video->companion_banner_path && file_exists(public_path($video->companion_banner_path))) {
                    @unlink(public_path($video->companion_banner_path));
                }
                $video->delete();
            }

            // 2. Delete the subversion
            $versionId = $subVersion->version_id;
            $subVersion->delete();

            // 3. Set latest remaining subversion as active (if any exist)
            $latest = SubVersion::where('version_id', $versionId)->latest('id')->first();
            if ($latest) {
                $latest->update(['is_active' => true]);
            }
        });

        return response()->json([
            'message' => 'Video SubVersion deleted successfully.'
        ]);
    }

    public function deleteSocialSubVersion($id)
    {
        $subVersion = SubVersion::findOrFail($id);
        $version = Version::findOrFail($subVersion->version_id);

        DB::transaction(function () use ($subVersion) {
            // 1. Remove all socials and their files associated with this subversion
            $socials = SubSocial::where('sub_version_id', $subVersion->id)->get();

            foreach ($socials as $social) {
                if ($social->path && file_exists(public_path($social->path))) {
                    @unlink(public_path($social->path));
                }
                $social->delete();
            }

            // 2. Delete the subversion
            $versionId = $subVersion->version_id;
            $subVersion->delete();

            // 3. Set latest remaining subversion as active (if any exist)
            $latest = SubVersion::where('version_id', $versionId)->latest('id')->first();
            if ($latest) {
                $latest->update(['is_active' => true]);
            }
        });

        return response()->json([
            'message' => 'Social SubVersion deleted successfully.',
        ]);
    }

    public function singleVideoDelete($id)
    {
        $subVideo = SubVideo::findOrFail($id);
        $filePath = public_path($subVideo->path);
        $companionBannerPath = public_path($subVideo->companion_banner_path);

        // Delete video file
        if (file_exists($filePath)) {
            @unlink($filePath);
        }

        // Delete companion banner file if exists
        if ($subVideo->companion_banner_path && file_exists($companionBannerPath)) {
            @unlink($companionBannerPath);
        }

        // Delete the SubVideo record
        $subVideo->delete();

        return response()->json([
            'message' => 'Video deleted successfully.',
            'subVersion_id' => $subVideo->sub_version_id,
        ]);
    }

    public function editVideoSubVersion($id)
    {
        $subVersion = SubVersion::with('version.preview')->findOrFail($id);
        $videoSizes = VideoSize::all();
        $preview = $subVersion->version->preview;

        return Inertia::render('Previews/Versions/SubVersions/Video/Edit', [
            'subVersionId' => $subVersion->id,
            'subVersionName' => $subVersion->name,
            'videoSizes' => $videoSizes,
            'preview' => [
                'id' => $preview->id,
                'name' => $preview->name,
            ],
        ]);
    }

    public function updateVideoSubVersion(Request $request, $id)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'videos' => ['nullable', 'array'],
            'videos.*.name' => ['nullable', 'string', 'max:255'],
            'videos.*.size_id' => ['nullable', 'exists:video_sizes,id'],
            'videos.*.codec' => ['nullable', 'string', 'max:255'],
            'videos.*.fps' => ['nullable', 'string', 'max:255'],
            'videos.*.path' => ['nullable', 'file', 'mimetypes:video/mp4,video/quicktime,video/x-msvideo,video/x-matroska,video/webm'],
            'videos.*.companion_banner_path' => ['nullable', 'file', 'mimes:jpg,jpeg,png,gif'],
            'videos.*.position' => ['nullable', 'integer'],
        ]);

        $subVersion = SubVersion::with('version.preview')->findOrFail($id);

        // 1. Update SubVersion name
        $subVersion->update([
            'name' => $request->name,
        ]);

        // 2. Remove all old SubVideo files and records
        $oldVideos = SubVideo::where('sub_version_id', $subVersion->id)->get();
        foreach ($oldVideos as $video) {
            if ($video->path && file_exists(public_path($video->path))) {
                @unlink(public_path($video->path));
            }
            if ($video->companion_banner_path && file_exists(public_path($video->companion_banner_path))) {
                @unlink(public_path($video->companion_banner_path));
            }
            $video->delete();
        }

        // 3. If videos are provided, save them
        if ($request->has('videos') && is_array($request->videos) && count($request->videos) > 0) {
            $uploadPath = public_path('uploads/videos');
            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }

            foreach ($request->videos as $index => $video) {
                // Only save if at least name, size_id, codec, and fps are present
                if (
                    empty($video['name']) &&
                    empty($video['size_id']) &&
                    empty($video['codec']) &&
                    empty($video['fps']) &&
                    empty($video['path']) &&
                    empty($video['companion_banner_path'])
                ) {
                    continue; // skip empty video slots
                }

                $name = $video['name'] ?? null;
                $sizeId = $video['size_id'] ?? null;
                $codec = $video['codec'] ?? null;
                $fps = $video['fps'] ?? null;
                $position = isset($video['position']) ? $video['position'] : $index;

                // Handle video file
                $videoPath = null;
                $aspect_ratio = null;
                $file_size = null;
                if (!empty($video['path'])) {
                    $file = $video['path'];
                    $ext = $file->getClientOriginalExtension();
                    $filename = ($name ?: 'video') . '_' . \Str::uuid() . '.' . $ext;
                    $file->move($uploadPath, $filename);
                    $videoPath = 'uploads/videos/' . $filename;

                    // Extract metadata
                    $getID3 = new \getID3;
                    $info = $getID3->analyze($uploadPath . '/' . $filename);

                    $width = $info['video']['resolution_x'] ?? null;
                    $height = $info['video']['resolution_y'] ?? null;
                    $aspect_ratio = $this->getAspectRatioString($width, $height);

                    $file_size_bytes = filesize($uploadPath . '/' . $filename);
                    $file_size = $file_size_bytes >= 1048576
                        ? round($file_size_bytes / 1048576, 2) . ' MB'
                        : round($file_size_bytes / 1024, 2) . ' KB';
                }

                // Handle companion banner if present
                $companionBannerPath = null;
                if (!empty($video['companion_banner_path'])) {
                    $bannerFile = $video['companion_banner_path'];
                    $bannerExt = $bannerFile->getClientOriginalExtension();
                    $bannerFilename = ($name ?: 'video') . '_companion_' . \Str::uuid() . '.' . $bannerExt;
                    $bannerUploadPath = public_path('uploads/videos/companions');
                    if (!is_dir($bannerUploadPath)) {
                        mkdir($bannerUploadPath, 0755, true);
                    }
                    $bannerFile->move($bannerUploadPath, $bannerFilename);
                    $companionBannerPath = 'uploads/videos/companions/' . $bannerFilename;
                }

                // Save SubVideo (fields can be null if not provided)
                SubVideo::create([
                    'sub_version_id' => $subVersion->id,
                    'name' => $name,
                    'path' => $videoPath,
                    'size_id' => $sizeId,
                    'codec' => $codec,
                    'aspect_ratio' => $aspect_ratio,
                    'fps' => $fps,
                    'file_size' => $file_size,
                    'companion_banner_path' => $companionBannerPath,
                    'position' => $position,
                ]);
            }
        }

        return response()->json([
            'redirect_to' => route('edit-video-subVersion', $subVersion->id),
            'message' => 'Video SubVersion updated successfully.',
        ]);
    }

    public function singleVideoEdit($id)
    {
        $subVideo = SubVideo::findOrFail($id);
        $subVersion = SubVersion::findOrFail($subVideo->sub_version_id);
        $version = Version::findOrFail($subVersion->version_id);
        $preview = Preview::findOrFail($version->preview_id);

        $videoSizes = VideoSize::all(['id', 'name', 'width', 'height']);

        return Inertia::render('Previews/Versions/SubVersions/Video/SingleEdit', [
            'subVideo' => [
                'id' => $subVideo->id,
                'name' => $subVideo->name,
                'size_id' => $subVideo->size_id,
                'codec' => $subVideo->codec,
                'fps' => $subVideo->fps,
                'path' => $subVideo->path,
                'companion_banner_path' => $subVideo->companion_banner_path,
            ],
            'videoSizes' => $videoSizes,
            'preview' => [
                'id' => $preview->id,
                'name' => $preview->name,
            ],
        ]);
    }

    public function singleVideoUpdate($id, Request $request)
    {
        $request->validate([
            'size_id' => ['required', 'exists:video_sizes,id'],
            'codec' => ['required', 'string', 'max:255'],
            'fps' => ['required', 'string', 'max:255'],
            'videoFile' => ['nullable', 'file', 'mimetypes:video/mp4,video/quicktime,video/x-msvideo,video/x-matroska,video/webm'],
            'companionBanner' => ['nullable', 'file', 'mimes:jpg,jpeg,png,gif'],
            'remove_companion_banner' => ['nullable', 'boolean'],
        ]);

        $subVideo = SubVideo::findOrFail($id);

        // --- Handle video file replacement ---
        if ($request->hasFile('videoFile')) {
            // Delete old video file
            if ($subVideo->path && file_exists(public_path($subVideo->path))) {
                @unlink(public_path($subVideo->path));
            }

            // Save new video file
            $file = $request->file('videoFile');
            $ext = $file->getClientOriginalExtension();
            $filename = $subVideo->name . '_' . \Str::uuid() . '.' . $ext;
            $uploadPath = public_path('uploads/videos');
            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }
            $file->move($uploadPath, $filename);
            $videoPath = $uploadPath . '/' . $filename;

            // Extract metadata
            $getID3 = new \getID3;
            $info = $getID3->analyze($videoPath);

            $width = $info['video']['resolution_x'] ?? null;
            $height = $info['video']['resolution_y'] ?? null;
            $aspect_ratio = $this->getAspectRatioString($width, $height);

            $file_size_bytes = filesize($videoPath);
            $file_size = $file_size_bytes >= 1048576
                ? round($file_size_bytes / 1048576, 2) . ' MB'
                : round($file_size_bytes / 1024, 2) . ' KB';

            $subVideo->path = 'uploads/videos/' . $filename;
            $subVideo->aspect_ratio = $aspect_ratio;
            $subVideo->file_size = $file_size;
        }

        // --- Handle companion banner replacement ---
        if ($request->hasFile('companionBanner')) {
            // Delete old companion banner file
            if ($subVideo->companion_banner_path && file_exists(public_path($subVideo->companion_banner_path))) {
                @unlink(public_path($subVideo->companion_banner_path));
            }

            // Save new companion banner file
            $bannerFile = $request->file('companionBanner');
            $bannerExt = $bannerFile->getClientOriginalExtension();
            $bannerFilename = $subVideo->name . '_companion_' . \Str::uuid() . '.' . $bannerExt;
            $bannerUploadPath = public_path('uploads/videos/companions');
            if (!is_dir($bannerUploadPath)) {
                mkdir($bannerUploadPath, 0755, true);
            }
            $bannerFile->move($bannerUploadPath, $bannerFilename);
            $subVideo->companion_banner_path = 'uploads/videos/companions/' . $bannerFilename;
        }

        // --- Handle companion banner removal ---
        if ($request->boolean('remove_companion_banner')) {
            if ($subVideo->companion_banner_path && file_exists(public_path($subVideo->companion_banner_path))) {
                @unlink(public_path($subVideo->companion_banner_path));
            }
            $subVideo->companion_banner_path = null;
        }

        // --- Update other fields ---
        $subVideo->size_id = $request->size_id;
        $subVideo->codec = $request->codec;
        $subVideo->fps = $request->fps;
        $subVideo->save();

        return response()->json([
            'redirect_to' => route('single-video-edit', $subVideo->id),
            'message' => 'Video updated successfully.',
        ]);
    }

    public function createGifSubVersion($id)
    {
        $version = Version::with('preview')->findOrFail($id);
        $preview = $version->preview;

        // Fetch banner sizes ordered by width and then height
        $bannerSizes = BannerSize::orderBy('width')->orderBy('height')->get(['id', 'width', 'height'])
            ->map(function ($s) {
                $s->label = "{$s->width}x{$s->height}";
                return $s;
            });

        return Inertia::render('Previews/Versions/SubVersions/Gif/Create', [
            'version' => $version,
            'preview' => $preview,
            'bannerSizes' => $bannerSizes,
        ]);
    }

    public function storeGifSubVersion(Request $request, $id)
    {
        $request->validate([
            'sub_version_name' => 'required|string|max:255',
            'gifs' => 'required|array|min:1',
            'gifs.*.file' => 'required|file|mimes:gif',
            'gifs.*.sizes' => 'required|array|size:1',
            'gifs.*.sizes.0' => 'required|exists:banner_sizes,id',
            'gifs.*.position' => 'required|integer',
        ]);

        $version = Version::findOrFail($id);

        DB::transaction(function () use ($request, $version) {
            // 1. Deactivate other subversions for this version
            SubVersion::where('version_id', $version->id)->update(['is_active' => false]);

            // 2. Create the new active subversion
            $subVersion = SubVersion::create([
                'version_id' => $version->id,
                'name' => $request->sub_version_name,
                'is_active' => true,
            ]);

            // 3. Handle GIF uploads
            $uploadPath = public_path('uploads/gifs');
            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }

            foreach ($request->gifs as $index => $gif) {
                if (empty($gif['file']) || empty($gif['sizes'][0])) {
                    continue;
                }

                $file = $gif['file'];
                $sizeId = $gif['sizes'][0];
                $position = $gif['position'] ?? $index;

                // Generate a unique filename
                $ext = $file->getClientOriginalExtension();
                $filename = 'gif_' . \Str::uuid() . '.' . $ext;

                // Get file size BEFORE move
                $sizeInBytes = $file->getSize();
                $fileSize = $sizeInBytes >= 1048576
                    ? round($sizeInBytes / 1048576, 2) . ' MB'
                    : round($sizeInBytes / 1024, 2) . ' KB';

                // Move the file
                $file->move($uploadPath, $filename);

                // Save SubGif
                SubGif::create([
                    'sub_version_id' => $subVersion->id,
                    'name' => $filename,
                    'path' => 'uploads/gifs/' . $filename,
                    'size_id' => $sizeId,
                    'file_size' => $fileSize,
                    'position' => $position,
                ]);
            }
        });

        return response()->json([
            'redirect_to' => route('previews-show', $version->preview_id),
            'message' => 'GIF Sub Version created successfully.',
        ]);
    }

    public function editGifSubVersion($id)
    {
        $subVersion = SubVersion::with([
            'version.preview',
            'gifs' => function ($q) {
                $q->orderBy('position');
            }
        ])->findOrFail($id);

        $version = $subVersion->version;
        $preview = $version->preview;

        // Fetch banner sizes ordered by width and then height
        $bannerSizes = BannerSize::orderBy('width')->orderBy('height')->get(['id', 'width', 'height'])
            ->map(function ($s) {
                $s->label = "{$s->width}x{$s->height}";
                return $s;
            });

        return Inertia::render('Previews/Versions/SubVersions/Gif/Edit', [
            'subVersion' => $subVersion,
            'version' => $version,
            'preview' => $preview,
            'bannerSizes' => $bannerSizes,
        ]);
    }

    public function updateGifSubVersion(Request $request, $id)
    {
        $request->validate([
            'sub_version_name' => 'required|string|max:255',
            'gifs' => 'required|array|min:1',
            'gifs.*.sizes' => 'required|array|size:1',
            'gifs.*.sizes.0' => 'required|exists:banner_sizes,id',
            'gifs.*.position' => 'required|integer',
            'gifs.*.file' => 'nullable|file|mimes:gif',
            'gifs.*.id' => 'nullable|integer|exists:sub_gifs,id',
        ]);

        $subVersion = SubVersion::findOrFail($id);
        $subVersion->name = $request->sub_version_name;
        $subVersion->save();

        // Check if any new GIFs are uploaded (i.e., any gif has a file)
        $hasNewGifs = collect($request->gifs)->contains(function ($gif) {
            return !empty($gif['file']);
        });

        if ($hasNewGifs) {
            // 1. Remove all existing GIFs and their files
            $oldGifs = SubGif::where('sub_version_id', $subVersion->id)->get();
            foreach ($oldGifs as $gif) {
                $filePath = public_path($gif->path);
                if (file_exists($filePath)) {
                    @unlink($filePath);
                }
                $gif->delete();
            }

            // 2. Insert new GIFs
            $uploadPath = public_path('uploads/gifs');
            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }

            foreach ($request->gifs as $gif) {
                if (empty($gif['file']) || empty($gif['sizes'][0])) {
                    continue;
                }

                $file = $gif['file'];
                $sizeId = $gif['sizes'][0];
                $position = $gif['position'];

                $ext = $file->getClientOriginalExtension();
                $filename = 'gif_' . \Str::uuid() . '.' . $ext;
                $sizeInBytes = $file->getSize();
                $fileSize = $sizeInBytes >= 1048576
                    ? round($sizeInBytes / 1048576, 2) . ' MB'
                    : round($sizeInBytes / 1024, 2) . ' KB';
                $file->move($uploadPath, $filename);

                SubGif::create([
                    'sub_version_id' => $subVersion->id,
                    'name' => $filename,
                    'path' => 'uploads/gifs/' . $filename,
                    'size_id' => $sizeId,
                    'file_size' => $fileSize,
                    'position' => $position,
                ]);
            }
        } else {
            // 1. Get all current GIF IDs for this subversion
            $currentGifIds = SubGif::where('sub_version_id', $subVersion->id)->pluck('id')->toArray();

            // 2. Get all GIF IDs sent from the frontend
            $sentGifIds = collect($request->gifs)->pluck('id')->filter()->toArray();

            // 4. Update positions for all GIFs in the request
            foreach ($request->gifs as $gif) {
                if (!empty($gif['id'])) {
                    $subGif = SubGif::find($gif['id']);
                    if ($subGif) {
                        $subGif->position = $gif['position'];
                        $subGif->save();
                    }
                }
            }
        }

        return redirect()->route('edit-gif-subVersion', $subVersion->id);
    }

    public function deleteGifSubVersion($id)
    {
        $subVersion = SubVersion::with(['gifs', 'version.preview'])->findOrFail($id);
        $versionId = $subVersion->version_id;
        $previewId = $subVersion->version->preview_id;

        // 1. Delete all GIF files and records for this subversion
        foreach ($subVersion->gifs as $gif) {
            $filePath = public_path($gif->path);
            if (file_exists($filePath)) {
                @unlink($filePath);
            }
            $gif->delete();
        }

        // 2. Delete the SubVersion
        $subVersion->delete();

        // 3. Set latest remaining subversion as active (if any exist)
        $latest = SubVersion::where('version_id', $versionId)->latest('id')->first();
        if ($latest) {
            $latest->update(['is_active' => true]);
        }

        return [
            'version_id' => $versionId,
        ];
    }

    public function singleGifEdit($id)
    {
        $subGif = SubGif::findOrFail($id);
        $subVersion = SubVersion::findOrFail($subGif->sub_version_id);
        $version = Version::findOrFail($subVersion->version_id);
        $preview = Preview::findOrFail($version->preview_id);

        // Get all available banner sizes
        $bannerSizes = BannerSize::orderBy('width')->orderBy('height')->get(['id', 'width', 'height'])
            ->map(function ($size) {
                return [
                    'id' => $size->id,
                    'label' => $size->width . 'x' . $size->height,
                ];
            });

        // Prepare the subGif data for the view
        $subGifData = [
            'id' => $subGif->id,
            'size_id' => $subGif->size_id,
            'name' => $subGif->name,
            'path' => $subGif->path,
        ];

        return Inertia::render('Previews/Versions/SubVersions/Gif/SingleEdit', [
            'subGif' => $subGifData,
            'bannerSizes' => $bannerSizes,
            'preview' => [
                'id' => $preview->id,
                'name' => $preview->name,
            ],
        ]);
    }

    public function singleGifUpdate($id, Request $request)
    {
        $request->validate([
            'size_id' => 'required|exists:banner_sizes,id',
            'file' => 'nullable|file|mimes:gif',
        ]);

        $subGif = SubGif::findOrFail($id);

        // If a new file is uploaded, replace the old one
        if ($request->hasFile('file')) {
            // Delete old file
            $oldPath = public_path($subGif->path);
            if (file_exists($oldPath)) {
                @unlink($oldPath);
            }

            // Save new file
            $file = $request->file('file');
            $ext = $file->getClientOriginalExtension();
            $filename = 'gif_' . \Str::uuid() . '.' . $ext;
            $uploadPath = public_path('uploads/gifs');
            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }
            $file->move($uploadPath, $filename);

            // Get file size
            $sizeInBytes = filesize($uploadPath . '/' . $filename);
            $fileSize = $sizeInBytes >= 1048576
                ? round($sizeInBytes / 1048576, 2) . ' MB'
                : round($sizeInBytes / 1024, 2) . ' KB';

            // Update DB fields
            $subGif->name = $filename;
            $subGif->path = 'uploads/gifs/' . $filename;
            $subGif->file_size = $fileSize;
        }

        // Always update size_id
        $subGif->size_id = $request->size_id;
        $subGif->save();

        return redirect()->route('single-gif-edit', $subGif->id)
            ->with('success', 'GIF updated successfully.');
    }

    public function singleGifDelete($id)
    {
        $subGif = SubGif::findOrFail($id);
        $filePath = public_path($subGif->path);

        // Delete the file if it exists
        if (file_exists($filePath)) {
            @unlink($filePath);
        }

        // Delete the SubGif record
        $subGif->delete();

        return response()->json([
            'message' => 'GIF deleted successfully.',
            'subVersion_id' => $subGif->sub_version_id,
        ]);
    }

    public function editBannerSubVersionPosition($id)
    {
        $subVersion = SubVersion::with([
            'banners' => function ($q) {
                $q->orderBy('position');
            },
            'version.preview'
        ])->findOrFail($id);

        $version = $subVersion->version;
        $preview = $version->preview;

        // Prepare banners with size label
        $bannerSizes = BannerSize::all(['id', 'width', 'height'])->keyBy('id');
        $banners = $subVersion->banners->map(function ($banner) use ($bannerSizes) {
            $size = $bannerSizes[$banner->size_id] ?? null;
            $banner->size_label = $size ? "{$size->width}x{$size->height}" : '';
            return $banner;
        });

        return Inertia::render('Previews/Versions/SubVersions/Banner/EditPosition', [
            'subVersion' => [
                'id' => $subVersion->id,
                'name' => $subVersion->name,
            ],
            'banners' => $banners,
            'preview' => [
                'id' => $preview->id,
                'name' => $preview->name,
            ],
            'version' => [
                'id' => $version->id,
                'name' => $version->name,
            ],
        ]);
    }

    public function updateBannerSubVersionPosition(Request $request, $id)
    {
        $request->validate([
            'banners' => 'required|array|min:1',
            'banners.*.id' => 'required|integer|exists:sub_banners,id',
            'banners.*.position' => 'required|integer',
        ]);

        $subVersion = SubVersion::findOrFail($id);

        foreach ($request->banners as $bannerData) {
            $banner = \App\Models\SubBanner::find($bannerData['id']);
            if ($banner && $banner->sub_version_id == $subVersion->id) {
                $banner->position = $bannerData['position'];
                $banner->save();
            }
        }

        return redirect()->route('edit-banner-sub-version-position', $subVersion->id)
            ->with('success', 'Banner positions updated successfully.');
    }

    public function editVideoSubVersionPosition($id)
    {
        dd('This video function is still on development. Will let you know soon!.');
    }

    public function editSocialSubVersionPosition($id)
    {
        dd('This social function is still on development. Will let you know soon!.');
    }
}
