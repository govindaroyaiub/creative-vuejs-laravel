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

class NewGifController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

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
    public function show(newGif $newGif)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(newGif $newGif)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, newGif $newGif, $id)
    {
        DB::beginTransaction();
        try {
            $gif = $newGif->findOrFail($id);

            // Delete previous image file
            if ($gif->path) {
                $oldPath = public_path($gif->path);
                if (file_exists($oldPath)) {
                    @unlink($oldPath);
                }
            }

            // Delete the previous row
            $gif->delete();

            // Create new gif row if a new file is uploaded
            if ($request->hasFile('file')) {
                $file = $request->file('file');
                // Get preview name from related version
                $previewName = $gif->version->feedbackSet->feedback->category->preview->name ?? 'gif';
                $safeName = Str::slug($previewName) . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $uploadDir = public_path('uploads/gifs');
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                $file->move($uploadDir, $safeName);

                // Calculate file size
                $filePath = $uploadDir . '/' . $safeName;
                $fileSizeBytes = filesize($filePath);
                $fileSize = $fileSizeBytes < 1048576
                    ? round($fileSizeBytes / 1024, 2) . ' KB'
                    : round($fileSizeBytes / 1048576, 2) . ' MB';

                // Create new row
                $newGifRow = newGif::create([
                    'name' => $safeName,
                    'size_id' => $request->input('size_id', 1),
                    'version_id' => $gif->version_id,
                    'position' => $request->input('position', 1),
                    'path' => 'uploads/gifs/' . $safeName,
                    'file_size' => $fileSize,
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
    public function destroy(newGif $newGif, $id)
    {
        DB::beginTransaction();
        try {
            $gif = $newGif->findOrFail($id);

            // Delete the file from the path
            if ($gif->path) {
                $filePath = public_path($gif->path);
                if (file_exists($filePath)) {
                    @unlink($filePath);
                }
            }

            // Delete the database record
            $gif->delete();

            DB::commit();
            return response('', 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response($e->getMessage(), 500);
        }
    }
}
