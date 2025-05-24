<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\User;
use App\Models\BannerSize;
use App\Models\Preview;
use App\Models\Version;
use App\Models\SubVersion;
use App\Models\SubBanner;
use App\Models\SubVideo;
use App\Models\SubSocial;
use App\Models\SubGif;
use App\Models\ColorPalette;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use ZipArchive;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Inertia\Inertia;

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
        $color_palettes = ColorPalette::find($preview->color_palette_id);
        $client = Client::find($preview->client_id);
        $all_colors = ColorPalette::where('status', 1)->select('id', 'primary')->get();

        $primary = $color_palettes->primary;
        $secondary = $color_palettes->secondary;
        $tertiary = $color_palettes->tertiary;
        $quaternary = $color_palettes->quaternary;

        $authUserClientName = Auth::check()
            ? (Client::find(Auth::user()->client_id)?->name ?? 'Unknown')
            : 'guest';

        return view('preview', compact(
            'preview',
            'versions',
            'subVersions',
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
            'clients' => Client::orderBy('name')->get(['id', 'name']),
            'users' => User::orderBy('name')->get(['id', 'name']),
            'colorPalettes' => ColorPalette::orderBy('name')->get(['id', 'name']),
            'bannerSizes' => BannerSize::orderBy('width')->orderBy('height')->get(['id', 'width', 'height'])->map(fn($s) => tap($s, fn($s) => $s->name = "{$s->width}x{$s->height}")),
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
        $request->validate([
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
            'banners' => 'nullable|array',
            'banners.*.file' => 'file|mimes:zip',
            'banners.*.size_id' => 'required_with:banners.*.file|exists:banner_sizes,id',
            'banners.*.position' => 'required|integer',
            'socials' => 'nullable|array',
            'socials.*.file' => 'required_with:socials|file|mimes:jpg,jpeg,png',
            'socials.*.name' => 'required_with:socials|string|max:255',
            'socials.*.position' => 'required_with:socials|integer',
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
                    'name' => $request->version_name ?: 'Master',
                    'description' => $request->version_description ?: 'Master Started',
                    'type' => 'banner',
                    'is_active' => true,
                ]);

                // 3. Create SubVersion
                $subVersion = $version->subVersions()->create([
                    'name' => $request->subversion_name ?: 'Version_1',
                    'is_active' => $request->boolean('subversion_active', true),
                ]);

                // 4. Handle Banner uploads
                foreach ($request->banners as $index => $banner) {
                    $file = $banner['file'];
                    $sizeId = $banner['size_id'];
                    $position = $banner['position'];
                    $sizeId = $banner['size_id'];
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

                    // Save SubBanner
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
        });

        return redirect()->route('previews-index')->with('success', 'Preview created successfully.');
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

        return Inertia::render('Previews/Versions/Create', [
            'preview' => $preview,
            'bannerSizes' => $bannerSizes,
        ]);
    }

    public function storeVersion(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:banner,social',
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
}
