<?php

namespace App\Http\Controllers;

use App\Models\newVersion;
use App\Models\newFeedbackSet;
use App\Models\newFeedback;
use App\Models\newBanner;
use App\Models\newVideo;
use App\Models\newGif;
use App\Models\newSocial;
use Illuminate\Http\Request;
use App\Models\BannerSize;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use ZipArchive;

class NewBannerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index() {}

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(newBanner $newBanner)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(newBanner $newBanner)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, newBanner $newBanner, $id)
    {
        DB::beginTransaction();
        try {
            $banner = $newBanner->findOrFail($id);

            // Update name, size, position if provided
            $banner->update([
                'size_id' => $request->input('size_id', $banner->size_id),
                // Add other fields if needed
            ]);

            // If a new file is uploaded, replace the file and update path/size
            if ($request->hasFile('file')) {
                // Delete previous banner folder
                if ($banner->path) {
                    $oldPath = public_path($banner->path);
                    if (is_dir($oldPath)) {
                        File::deleteDirectory($oldPath);
                    }
                }

                $previewName = str_replace(' ', '_', $banner->version->feedbackset->feedback->category->preview->name ?? 'banner');
                $uniqueSuffix = uniqid('_');
                $uploadDir = public_path("uploads/banners");
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                $zipName = $previewName . $uniqueSuffix . '.zip';
                $zipPath = $uploadDir . '/' . $zipName;
                $request->file('file')->move($uploadDir, $zipName);

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
                $zip = new ZipArchive;
                if ($zip->open($zipPath) === TRUE) {
                    $zip->extractTo($extractDir);
                    $zip->close();
                    unlink($zipPath); // Delete zip after extraction
                }

                $banner->update([
                    'name' => $zipName,
                    'path' => "uploads/banners/{$previewName}{$uniqueSuffix}/",
                    'file_size' => $zipSize,
                ]);
            }

            DB::commit();
            return response('', 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response($e->getMessage(), 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(newBanner $newBanner, $id)
    {
        DB::beginTransaction();
        try {
            $banner = $newBanner->findOrFail($id);

            // Delete the folder from the path
            if ($banner->path) {
                $bannerPath = public_path($banner->path);
                if (is_dir($bannerPath)) {
                    File::deleteDirectory($bannerPath);
                }
            }

            // Delete the row
            $banner->delete();

            DB::commit();
            return response('', 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response($e->getMessage(), 500);
        }
    }
}
