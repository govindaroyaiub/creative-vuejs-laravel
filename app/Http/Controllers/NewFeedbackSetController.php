<?php

namespace App\Http\Controllers;

use App\Models\newFeedbackSet;
use App\Models\newFeedback;
use App\Models\newBanner;
use App\Models\newVideo;
use App\Models\newSocial;
use App\Models\newGif;
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
    public function create($id) {}

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, $id) {}

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
    public function destroy(newFeedbackSet $newFeedbackSet, $id)
    {
        DB::beginTransaction();
        try {
            // Get the set to delete
            $set = $newFeedbackSet->findOrFail($id);

            // Get the category type for folder/file deletion
            $category = $set->feedback->category;
            $folderMap = [
                'banner' => 'uploads/banners',
                'video' => 'uploads/videos',
                'gif' => 'uploads/gifs',
                'social' => 'uploads/socials',
            ];
            $folder = $folderMap[$category->type] ?? null;

            // Delete all versions and banners/assets
            foreach ($set->versions as $version) {
                if ($category->type === 'banner') {
                    foreach ($version->banners as $banner) {
                        // Delete banner folder/file based on category type
                        if ($folder && $banner->path) {
                            $bannerPath = public_path($banner->path);
                            if (is_dir($bannerPath)) {
                                File::deleteDirectory($bannerPath);
                            }
                        }
                        $banner->delete();
                    }
                }
                if ($category->type === 'video') {
                }
                if ($category->type === 'social') {
                    foreach ($version->socials as $social) {
                        // Delete social image file
                        if ($folder && $social->path) {
                            $socialPath = public_path($social->path);
                            if (file_exists($socialPath)) {
                                @unlink($socialPath);
                            }
                        }
                        $social->delete();
                    }
                }
                if ($category->type === 'gif') {
                }
                $version->delete();
            }

            // Delete the set itself
            $set->delete();

            DB::commit();
            return response('', 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response($e->getMessage(), 500);
        }
    }
}
