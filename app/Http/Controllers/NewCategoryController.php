<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use ZipArchive;
use Inertia\Inertia;
use getID3;
use App\Models\newCategory;
use App\Models\newFeedback;
use App\Models\newFeedbackSet;
use App\Models\newVersion;
use App\Models\newPreview;
use App\Models\newBanner;
use App\Models\BannerSize;
use App\Models\VideoSize;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class NewCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(newPreview $preview) {}


    /**
     * Show the form for creating a new resource.
     */
    public function create($id)
    {
        $preview = newPreview::findOrFail($id);

        return Inertia::render('Previews/Categories/Create', [
            'preview' => $preview,
            'bannerSizes' => BannerSize::orderBy('width')->orderBy('height')->get(['id', 'width', 'height'])->map(fn($s) => tap($s, fn($s) => $s->name = "{$s->width}x{$s->height}")),
            'videoSizes' => VideoSize::orderBy('width')->orderBy('height')->get(['id', 'width', 'height'])->map(fn($s) => tap($s, fn($s) => $s->name = "{$s->width}x{$s->height}")),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, $id)
    {
        $validated = $request->validate([
            'category_name' => ['required', 'string', 'max:255'],  // (2)
            'feedback_name' => ['required', 'string', 'max:255'],  // (3)
            'description' => ['required', 'string', 'max:255'],  // (3)
            'sets' => ['array'],
            'sets.*.name' => ['nullable', 'string', 'max:255'],
            'sets.*.versions' => ['array'],
            'sets.*.versions.*.name' => ['nullable', 'string', 'max:255'],
            'sets.*.versions.*.banners' => ['array'],
            'sets.*.versions.*.banners.*.size_id' => ['required', 'integer', 'exists:banner_sizes,id'],
        ]);

        $preview = newPreview::with('categories')->findOrFail($id);

        $uploadPath = public_path('uploads/banners');
        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0755, true);
        }

        DB::beginTransaction();

        try {
            // 1) Deactivate all categories of this preview
            $preview->categories()->update(['is_active' => false]);

            // 2) Create new active category
            /** @var Category $category */
            $category = $preview->categories()->create([
                'name'       => $validated['category_name'],
                'is_active'  => true,
            ]);

            // 3) Create new feedback under that category
            /** @var NewFeedback $feedback */
            $feedback = $category->feedbacks()->create([
                'name'        => $validated['feedback_name'],
                'description' => $validated['description'],
                // include preview FK if your feedbacks table has it:
                'preview_id'  => $preview->id,
                'is_active'   => true,
            ]);

            // 4) Your existing Sets → Versions → Banners flow (unchanged logic)
            foreach ((array) ($validated['sets'] ?? []) as $setIndex => $setData) {
                $feedbackSet = $feedback->feedbackSets()->create([
                    'name'     => $setData['name'] ?? null,
                    'position' => $setIndex,
                ]);

                foreach ((array) ($setData['versions'] ?? []) as $vIndex => $versionData) {
                    $version = $feedbackSet->versions()->create([
                        'name'     => $versionData['name'] ?? null,
                        'position' => $vIndex,
                    ]);

                    foreach ((array) ($versionData['banners'] ?? []) as $bIndex => $bannerData) {
                        // Fetch nested file
                        $file = $request->file("sets.$setIndex.versions.$vIndex.banners.$bIndex.file");
                        if (!$file) continue;
                        if (strtolower($file->getClientOriginalExtension()) !== 'zip') continue;

                        $size = BannerSize::findOrFail((int)$bannerData['size_id']);
                        $dimension = "{$size->width}x{$size->height}";

                        // Base name from request->name if provided, else preview name
                        $baseName = $request->input('name') ?: ($preview->name ?? 'banner');
                        $zipName  = $baseName . '_' . $dimension . '_' . Str::uuid() . '.zip';

                        // Move zip
                        $file->move($uploadPath, $zipName);
                        $zipFullPath = $uploadPath . '/' . $zipName;

                        // File size (display)
                        $sizeInBytes = filesize($zipFullPath);
                        $fileSize = $sizeInBytes >= 1048576
                            ? round($sizeInBytes / 1048576, 2) . ' MB'
                            : round($sizeInBytes / 1024, 2) . ' KB';

                        // Extract into folder named after zip (no path changes)
                        $extractedFolder = $uploadPath . '/' . pathinfo($zipName, PATHINFO_FILENAME);
                        if (!is_dir($extractedFolder)) {
                            mkdir($extractedFolder, 0755, true);
                        }

                        $zip = new ZipArchive;
                        if ($zip->open($zipFullPath) === true) {
                            // minimal zip-slip check
                            for ($i = 0; $i < $zip->numFiles; $i++) {
                                $entry = $zip->getNameIndex($i);
                                if (str_contains($entry, '../') || str_starts_with($entry, '/')) {
                                    $zip->close();
                                    @unlink($zipFullPath);
                                    throw new \RuntimeException('Unsafe ZIP contents detected.');
                                }
                            }
                            $zip->extractTo($extractedFolder);
                            $zip->close();
                            @unlink($zipFullPath);
                        } else {
                            throw new \RuntimeException("Failed to extract: $zipName");
                        }

                        $publicRelativePath = 'uploads/banners/' . basename($extractedFolder);

                        NewBanner::create([
                            'version_id' => $version->id,
                            'name'       => $preview->name,
                            'path'       => $publicRelativePath,
                            'size_id'    => $size->id,
                            'file_size'  => $fileSize,
                            'position'   => (int)($bannerData['position'] ?? $bIndex),
                        ]);
                    }
                }
            }

            DB::commit();

            // Axios client expects { ok, redirect }
            return response()->json([
                'ok'       => true,
                'redirect' => route('previews-show', $preview->slug),
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);

            // Return JSON error for axios (so your Swal can show message)
            return response()->json([
                'ok'      => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(newCategory $newCategory)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(newCategory $newCategory)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, newCategory $newCategory)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(newCategory $newCategory)
    {
        //
    }
}
