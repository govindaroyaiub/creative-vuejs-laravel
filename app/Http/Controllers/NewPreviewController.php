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
                'slug' => Str::uuid()->toString(),
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
        $preview = newPreview::query()
            ->with([
                'categories:id,preview_id,name',
                'categories.feedbacks:id,category_id,name',
                'categories.feedbacks.feedbackSets:id,feedback_id,name',
                'categories.feedbacks.feedbackSets.versions:id,feedback_set_id,name',

                'categories.feedbacks.feedbackSets.versions.banners:id,version_id,id,size_id,path,position',
                'categories.feedbacks.feedbackSets.versions.videos:id,version_id,id,size_id,path,position',
                'categories.feedbacks.feedbackSets.versions.gifs:id,version_id,id,size_id,path,position',
                'categories.feedbacks.feedbackSets.versions.socials:id,version_id,id,path,position', // ← add this
            ])
            ->findOrFail($id);

        $bannerSizes = BannerSize::query()->select('id', 'width', 'height')->orderBy('width')->get();
        $videoSizes  = VideoSize::query()->select('id', 'name', 'width', 'height')->orderBy('width')->get();

        return Inertia::render('Previews/Update', [
            'preview'      => $preview,
            'bannerSizes'  => $bannerSizes,
            'videoSizes'   => $videoSizes,
            // optionally a concurrency token:
            'previewETag'  => $preview->updated_at->toISOString(),
        ]);
    }

    private function kindForVersion(int $versionId): string
    {
        // category.name is one of: banner | video | gif | social
        return DB::table('new_categories')
            ->join('new_feedback', 'new_feedback.category_id', '=', 'new_categories.id')
            ->join('new_feedback_sets', 'new_feedback_sets.feedback_id', '=', 'new_feedback.id')
            ->join('new_versions', 'new_versions.feedback_set_id', '=', 'new_feedback_sets.id')
            ->where('new_versions.id', $versionId)
            ->value('new_categories.name');
    }

    private function modelForKind(string $kind): string
    {
        return match ($kind) {
            'banner' => \App\Models\newBanner::class,
            'video'  => \App\Models\newVideo::class,
            'gif'    => \App\Models\newGif::class,
            'social' => \App\Models\newSocial::class,
            default  => throw new \RuntimeException("Unknown kind $kind"),
        };
    }

    // Convenience when appending new files
    private function nextPosition(string $table, int $versionId): int
    {
        return (int) DB::table($table)->where('version_id', $versionId)->max('position') + 1;
    }

    public function bulkEdit(Request $request, newPreview $preview)
    {
        // 1) Validate the envelope (keep flexible)
        $data = $request->validate([
            'etag'         => ['nullable', 'string'],
            'type'         => ['nullable', 'in:banner,video,gif,social'], // we’ll use banner path for files
            'categories'   => ['required', 'array'],
            'feedbacks'    => ['required', 'array'],
            'feedbackSets' => ['required', 'array'],
            'versions'     => ['required', 'array'],
            'banners'      => ['required', 'array'],
            'videos'       => ['required', 'array'],
            'gifs'         => ['required', 'array'],
            'socials'      => ['required', 'array'],
            'fileReorders' => ['array'],
        ]);

        // 2) Optional optimistic concurrency
        if (!empty($data['etag']) && $preview->updated_at?->toISOString() !== $data['etag']) {
            return response()->json(['error' => 'Preview changed on the server. Please reload.'], 409);
        }

        // 3) Early exit if nothing to do
        $empty = fn($b) => empty($b['created']) && empty($b['updated']) && empty($b['deleted']);
        if (
            $empty($data['categories']) && $empty($data['feedbacks']) && $empty($data['feedbackSets']) &&
            $empty($data['versions'])   && $empty($data['banners'])   && $empty($data['videos']) &&
            $empty($data['gifs'])       && $empty($data['socials'])   && empty($data['fileReorders'])
        ) {
            return response()->json(['success' => 'No changes.']);
        }

        // 4) Helpers (small & explicit)
        $onlyIds = function (array $vals): array {
            $out = [];
            foreach ($vals as $v) {
                if (is_int($v)) {
                    $out[] = $v;
                    continue;
                }
                if (is_string($v) && ctype_digit($v)) {
                    $out[] = (int) $v;
                }
            }
            return $out;
        };
        $resolveParent = function ($key, array $map) {
            // supports numeric ids and tempIds -> real ids (from $map)
            if ($key === null) return null;
            if (is_int($key)) return $key;
            if (is_string($key) && ctype_digit($key)) return (int)$key;
            if (is_string($key) && str_starts_with($key, 'tmp-')) return $map[$key] ?? null;
            return null;
        };
        $humanSize = function (int $bytes): string {
            if ($bytes < 1024) return $bytes . ' B';
            $units = ['KB', 'MB', 'GB', 'TB'];
            $val = $bytes / 1024;
            $i = 0;
            while ($val >= 1024 && $i < count($units) - 1) {
                $val /= 1024;
                $i++;
            }
            return number_format($val, 2) . ' ' . $units[$i];
        };
        $resolveZipAbs = function (string $p): string {
            // absolute?
            if (str_starts_with($p, DIRECTORY_SEPARATOR) || preg_match('/^[A-Za-z]:[\\\\\\/]/', $p)) return $p;
            $candidates = [
                public_path($p),
                storage_path('app/public/' . ltrim($p, '/')),
                storage_path('app/' . ltrim($p, '/')),
                base_path($p),
            ];
            foreach ($candidates as $c) if (is_file($c)) return $c;
            return public_path($p); // last attempt
        };
        $extractBannerZip = function (string $relativeOrAbsolute) use ($resolveZipAbs, $humanSize): array {
            $abs = $resolveZipAbs($relativeOrAbsolute);
            if (!is_file($abs)) {
                throw new \RuntimeException("Banner ZIP not found: {$relativeOrAbsolute}");
            }
            $zipNameNoExt = pathinfo($abs, PATHINFO_FILENAME);
            $size = filesize($abs) ?: 0;
            $human = $humanSize($size);

            $targetBase = public_path('uploads/banners');
            if (!is_dir($targetBase)) mkdir($targetBase, 0755, true);
            $targetAbs = $targetBase . DIRECTORY_SEPARATOR . $zipNameNoExt;
            if (!is_dir($targetAbs)) mkdir($targetAbs, 0755, true);

            $zip = new ZipArchive;
            if ($zip->open($abs) !== true) {
                throw new \RuntimeException("Failed to open ZIP: {$relativeOrAbsolute}");
            }
            $zip->extractTo($targetAbs);
            $zip->close();
            @unlink($abs);

            return ['uploads/banners/' . $zipNameNoExt, $human]; // [folder path, human size]
        };

        try {
            $result = DB::transaction(function () use ($preview, $data, $onlyIds, $resolveParent, $extractBannerZip) {

                // tempId -> real id maps for creates
                $mapCat = [];
                $mapFb = [];
                $mapSet = [];
                $mapVer = [];

                /* ========== CREATES (top → down) ========== */

                foreach (($data['categories']['created'] ?? []) as $row) {
                    $m = new newCategory(['preview_id' => $preview->id, 'name' => $row['name'] ?? '']);
                    $m->save();
                    if (!empty($row['tempId'])) $mapCat[$row['tempId']] = $m->id;
                }

                foreach (($data['feedbacks']['created'] ?? []) as $row) {
                    $catId = $resolveParent($row['category_id'] ?? null, $mapCat);
                    if (!$catId) continue;
                    $m = new newFeedback(['category_id' => $catId, 'name' => $row['name'] ?? '']);
                    $m->save();
                    if (!empty($row['tempId'])) $mapFb[$row['tempId']] = $m->id;
                }

                foreach (($data['feedbackSets']['created'] ?? []) as $row) {
                    $fbId = $resolveParent($row['feedback_id'] ?? null, $mapFb);
                    if (!$fbId) continue;
                    $m = new newFeedbackSet(['feedback_id' => $fbId, 'name' => $row['name'] ?? '']);
                    $m->save();
                    if (!empty($row['tempId'])) $mapSet[$row['tempId']] = $m->id;
                }

                foreach (($data['versions']['created'] ?? []) as $row) {
                    $setId = $resolveParent($row['feedback_set_id'] ?? null, $mapSet);
                    if (!$setId) continue;
                    $m = new newVersion(['feedback_set_id' => $setId, 'name' => $row['name'] ?? '']);
                    $m->save();
                    if (!empty($row['tempId'])) $mapVer[$row['tempId']] = $m->id;
                }

                // Banners (created) – simple, explicit
                $createdBannerCount = 0;
                foreach (($data['banners']['created'] ?? []) as $row) {
                    $verId = $resolveParent($row['version_id'] ?? null, $mapVer);
                    if (!$verId) continue;

                    $rawPath  = trim((string)($row['path'] ?? ''));
                    $sizeId   = $row['size_id'] ?? null;
                    $position = (int)($row['position'] ?? 0);
                    $name     = $row['name'] ?? $preview->name;

                    if (!$rawPath) throw new \RuntimeException('Banner path is required.');
                    if (preg_match('/\.zip$/i', $rawPath)) {
                        [$folder, $human] = $extractBannerZip($rawPath);
                        newBanner::create([
                            'version_id' => $verId,
                            'name'       => $name,
                            'path'       => $folder,
                            'size_id'    => $sizeId,
                            'file_size'  => $human,
                            'position'   => $position,
                        ]);
                    } else {
                        // already a folder path
                        newBanner::create([
                            'version_id' => $verId,
                            'name'       => $name,
                            'path'       => $rawPath,
                            'size_id'    => $sizeId,
                            'file_size'  => '0 KB',
                            'position'   => $position,
                        ]);
                    }
                    $createdBannerCount++;
                }

                /* ========== UPDATES (flat & explicit) ========== */

                foreach (($data['categories']['updated'] ?? []) as $row)
                    if (!empty($row['id'])) newCategory::whereKey((int)$row['id'])->update(['name' => $row['name'] ?? '']);

                foreach (($data['feedbacks']['updated'] ?? []) as $row)
                    if (!empty($row['id'])) newFeedback::whereKey((int)$row['id'])->update(['name' => $row['name'] ?? '']);

                foreach (($data['feedbackSets']['updated'] ?? []) as $row)
                    if (!empty($row['id'])) newFeedbackSet::whereKey((int)$row['id'])->update(['name' => $row['name'] ?? '']);

                foreach (($data['versions']['updated'] ?? []) as $row)
                    if (!empty($row['id'])) newVersion::whereKey((int)$row['id'])->update(['name' => $row['name'] ?? '']);

                // Banners updated
                foreach (($data['banners']['updated'] ?? []) as $row) {
                    if (empty($row['id'])) continue;
                    $payload = [];
                    if (array_key_exists('size_id', $row))  $payload['size_id']  = $row['size_id'];
                    if (array_key_exists('position', $row)) $payload['position'] = (int)$row['position'];
                    if (!empty($row['path'])) {
                        $p = trim((string)$row['path']);
                        if (preg_match('/\.zip$/i', $p)) {
                            [$folder, $human] = $extractBannerZip($p);
                            $payload['path'] = $folder;
                            $payload['file_size'] = $human;
                        } else {
                            $payload['path'] = $p;
                        }
                    }
                    if ($payload) newBanner::whereKey((int)$row['id'])->update($payload);
                }

                /* ========== REORDERS (banners only for now) ========== */

                foreach (($data['fileReorders'] ?? []) as $r) {
                    $ids = $onlyIds($r['idsInOrder'] ?? []);
                    $pos = 0;
                    foreach ($ids as $id) newBanner::whereKey($id)->update(['position' => $pos++]);
                }

                /* ========== DELETES (expand, clean FS, delete bottom→up) ========== */

                $toDel = [
                    'categories'   => $onlyIds($data['categories']['deleted']  ?? []),
                    'feedbacks'    => $onlyIds($data['feedbacks']['deleted']   ?? []),
                    'feedbackSets' => $onlyIds($data['feedbackSets']['deleted'] ?? []),
                    'versions'     => $onlyIds($data['versions']['deleted']    ?? []),
                    'banners'      => $onlyIds($data['banners']['deleted']     ?? []),
                    // videos/gifs/socials omitted on purpose to keep it simple now
                ];

                // Expand descendants
                if ($toDel['categories']) {
                    $fbIds = newFeedback::whereIn('category_id', $toDel['categories'])->pluck('id')->all();
                    $toDel['feedbacks'] = array_values(array_unique(array_merge($toDel['feedbacks'], $fbIds)));
                }
                if ($toDel['feedbacks']) {
                    $setIds = newFeedbackSet::whereIn('feedback_id', $toDel['feedbacks'])->pluck('id')->all();
                    $toDel['feedbackSets'] = array_values(array_unique(array_merge($toDel['feedbackSets'], $setIds)));
                }
                if ($toDel['feedbackSets']) {
                    $verIds = newVersion::whereIn('feedback_set_id', $toDel['feedbackSets'])->pluck('id')->all();
                    $toDel['versions'] = array_values(array_unique(array_merge($toDel['versions'], $verIds)));
                }
                if ($toDel['versions']) {
                    $toDel['banners'] = array_values(array_unique(array_merge(
                        $toDel['banners'],
                        newBanner::whereIn('version_id', $toDel['versions'])->pluck('id')->all()
                    )));
                }

                // Filesystem cleanup for banners first
                if (!empty($toDel['banners'])) {
                    foreach (newBanner::whereIn('id', $toDel['banners'])->get(['path']) as $b) {
                        $this->deletePath($b->path); // removes extracted folder
                    }
                }

                // DB deletes bottom→up
                $deleted = [
                    'banners'      => 0,
                    'versions'     => 0,
                    'feedbackSets' => 0,
                    'feedbacks'    => 0,
                    'categories'   => 0,
                ];
                if ($toDel['banners'])      $deleted['banners']      = newBanner::whereIn('id', $toDel['banners'])->delete();
                if ($toDel['versions'])     $deleted['versions']     = newVersion::whereIn('id', $toDel['versions'])->delete();
                if ($toDel['feedbackSets']) $deleted['feedbackSets'] = newFeedbackSet::whereIn('id', $toDel['feedbackSets'])->delete();
                if ($toDel['feedbacks'])    $deleted['feedbacks']    = newFeedback::whereIn('id', $toDel['feedbacks'])->delete();
                if ($toDel['categories'])   $deleted['categories']   = newCategory::whereIn('id', $toDel['categories'])->delete();

                // Touch for a fresh ETag
                $preview->touch();

                return ['created_banners' => $createdBannerCount, 'deleted' => $deleted];
            });

            return response()->json([
                'success' => 'Preview saved successfully.',
                'stats'   => $result,
            ]);
        } catch (\RuntimeException $e) {
            report($e);
            return response()->json(['error' => $e->getMessage()], 422); // clear client error (e.g., ZIP not found)
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['error' => 'Failed to save preview.'], 500);
        }
    }
}
