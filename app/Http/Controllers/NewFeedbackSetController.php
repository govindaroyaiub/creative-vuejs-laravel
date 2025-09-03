<?php

namespace App\Http\Controllers;

use App\Models\newFeedbackSet;
use App\Models\newFeedback;
use App\Models\newBanner;
use Illuminate\Http\Request;
use App\Models\BannerSize;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use ZipArchive;

class NewFeedbackSetController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index() {}

    /**
     * Show the form for creating a new resource.
     */
    public function create($id)
    {
        $feedback = newFeedback::find($id);
        $feedbackId = $feedback->id;

        return Inertia::render('Previews/Categories/Feedbacks/FeedbackSets/Create', [
            'bannerSizes' => BannerSize::orderBy('width')->orderBy('height')->get(['id', 'width', 'height'])->map(fn($s) => tap($s, fn($s) => $s->name = "{$s->width}x{$s->height}")),
            'feedbackId' => (int) $feedbackId,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, $id)
    {
        $feedback = newFeedback::findOrFail($id);

        // If you truly must access via category->preview, uncomment:
        // $preview = optional($feedback->category)->preview ?? abort(404, 'Preview not found for this feedback');

        $preview = $feedback->category->preview ?? abort(404, 'Preview not found for this feedback');

        // 2) Basic shape validation (tweak rules as you need)
        $validated = $request->validate([
            'sets' => ['array'],
            'sets.*.name' => ['nullable', 'string', 'max:255'],
            'sets.*.versions' => ['array'],
            'sets.*.versions.*.name' => ['nullable', 'string', 'max:255'],
            'sets.*.versions.*.banners' => ['array'],
            'sets.*.versions.*.banners.*.size_id' => ['required', 'integer', 'exists:banner_sizes,id'],
            // files are nested; they come via $request->file() per index
        ]);

        // Base upload path per feedback (still under /public as you prefer)
        $uploadPath = public_path('uploads/banners');
        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0755, true);
        }

        DB::beginTransaction();

        try {
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
                        $file = $request->file("sets.$setIndex.versions.$vIndex.banners.$bIndex.file");
                        if (!$file) continue;
                        if (strtolower($file->getClientOriginalExtension()) !== 'zip') continue;

                        $size    = BannerSize::findOrFail((int)$bannerData['size_id']);
                        $dimension = "{$size->width}x{$size->height}";

                        // ✅ Keep file naming logic; use preview->name if $request->name not present
                        $baseName = $request->input('name') ?: ($preview->name ?? 'banner');
                        $zipName  = $baseName . '_' . $dimension . '_' . Str::uuid() . '.zip';

                        // File size (before extraction)
                        $file->move($uploadPath, $zipName);
                        $zipFullPath = $uploadPath . '/' . $zipName;

                        $sizeInBytes = filesize($zipFullPath);
                        $fileSize = $sizeInBytes >= 1048576
                            ? round($sizeInBytes / 1048576, 2) . ' MB'
                            : round($sizeInBytes / 1024, 2) . ' KB';

                        // Extract to folder named after the zip (✅ same as your original)
                        $extractedFolder = $uploadPath . '/' . pathinfo($zipName, PATHINFO_FILENAME);
                        if (!is_dir($extractedFolder)) {
                            mkdir($extractedFolder, 0755, true);
                        }

                        $zip = new ZipArchive;
                        if ($zip->open($zipFullPath) === true) {
                            // minimal zip-slip guard
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
                            @unlink($zipFullPath); // delete original zip
                        } else {
                            throw new \RuntimeException("Failed to extract: $zipName");
                        }

                        // ✅ Path saved exactly like before
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
            return response()->json([
                'ok' => true,
                'redirect' => route('previews-show', $preview->slug),
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);
            return back()->withErrors(['upload' => $e->getMessage()])->withInput();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(newFeedbackSet $newFeedbackSet)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(newFeedbackSet $newFeedbackSet)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, newFeedbackSet $newFeedbackSet)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(newFeedbackSet $newFeedbackSet)
    {
        //
    }
}
