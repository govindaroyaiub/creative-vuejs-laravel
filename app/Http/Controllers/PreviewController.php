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
use Inertia\Inertia;

class PreviewController extends Controller
{
    public function show($id)
    {
        $preview_id = $id;
        $preview = Preview::with(['client.colorPalette', 'colorPalette', 'uploader'])->findOrFail($id);
        // dd($preview);
        $versions = Version::where('preview_id', $id)->get();
        $subVersions = SubVersion::whereIn('version_id', $versions->pluck('id'))->get();
        $color_palettes = ColorPalette::where('id', $preview['color_palette_id'])->first();
        $client = Client::where('id', $preview['client_id'])->first();
        $primary = $color_palettes['primary'];
        $secondary = $color_palettes['secondary'];
        $tertiary = $color_palettes['tertiary'];
        $quaternary = $color_palettes['quaternary'];

        if (Auth::user()) {
            $authUserClientId = Auth::user()->client_id;
            $authUserClientInfo = Client::find($authUserClientId);
            $authUserClientName = $authUserClientInfo['name'];
        }
        else{
            $authUserClientName = 'guest';
        }

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
            'preview_id'
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
        });

        return redirect()->route('previews-index')->with('success', 'Preview created successfully.');
    }

    public function edit(Preview $preview)
    {
        return inertia('Previews/Edit', [
            'preview' => $preview,
            'clients' => Client::all(),
            'users' => User::all(),
            'palettes' => ColorPalette::all(),
        ]);
    }

    public function update(Request $request, Preview $preview)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'client_id' => 'required|exists:clients,id',
            'team_members' => 'required|array',
            'team_members.*' => 'exists:users,id',
            'uploader_id' => 'required|exists:users,id',
            'color_palette_id' => 'nullable|exists:color_palettes,id',
        ]);

        $preview->update($validated);

        return redirect()->route('previews-index')->with('success', 'Preview updated.');
    }

    public function destroy($id)
    {
        DB::transaction(function () use ($id) {
            $preview = Preview::with('versions.subVersions')->findOrFail($id);

            foreach ($preview->versions as $version) {
                foreach ($version->subVersions as $subVersion) {
                    // Delete related SubBanner (if exists)
                    if ($subBanner = $subVersion->subBanner) {
                        $this->deletePathFromDisk($subBanner->path);
                        $subBanner->delete();
                    }

                    // Delete related SubVideo (if exists)
                    if ($subVideo = $subVersion->subVideo) {
                        $this->deletePathFromDisk($subVideo->path);
                        if ($subVideo->companion_banner_path) {
                            $this->deletePathFromDisk($subVideo->companion_banner_path);
                        }
                        $subVideo->delete();
                    }

                    // Delete related SubSocial (if exists)
                    if ($subSocial = $subVersion->subSocial) {
                        $this->deletePathFromDisk($subSocial->path);
                        $subSocial->delete();
                    }

                    // Delete related SubGif (if exists)
                    if ($subGif = $subVersion->subGif) {
                        $this->deletePathFromDisk($subGif->path);
                        $subGif->delete();
                    }

                    $subVersion->delete();
                }

                $version->delete();
            }

            $preview->delete();
        });

        return redirect()->route('previews-index')->with('success', 'Preview and all related data deleted.');
    }

    /**
     * Delete folder or file path from public directory.
     */
    protected function deletePathFromDisk($relativePath)
    {
        $fullPath = public_path($relativePath);

        if (File::exists($fullPath)) {
            if (File::isDirectory($fullPath)) {
                File::deleteDirectory($fullPath);
            } else {
                File::delete($fullPath);
            }
        }
    }
}
