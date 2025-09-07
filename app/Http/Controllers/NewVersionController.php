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

class NewVersionController extends Controller
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
    public function show(newVersion $newVersion)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(newVersion $newVersion)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, newVersion $newVersion)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(newVersion $newVersion, $id)
    {
        DB::beginTransaction();
        try {
            // Get the version to delete
            $version = $newVersion->findOrFail($id);

            // Get the category type for folder/file deletion
            $category = $version->feedbackSet->feedback->category;
            $folderMap = [
                'banner' => 'uploads/banners',
                'video' => 'uploads/videos',
                'gif' => 'uploads/gifs',
                'social' => 'uploads/socials',
            ];
            $folder = $folderMap[$category->type] ?? null;

            // Delete all banners/assets
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
                foreach ($version->videos as $video) {
                    // Delete video file
                    if ($folder && $video->path) {
                        $videoPath = public_path($video->path);
                        if (file_exists($videoPath)) {
                            @unlink($videoPath);
                        }
                    }
                    // Delete companion banner file
                    if ($folder && $video->companion_banner_path) {
                        $bannerPath = public_path($video->companion_banner_path);
                        if (file_exists($bannerPath)) {
                            @unlink($bannerPath);
                        }
                    }
                    $video->delete();
                }
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
                foreach ($version->gifs as $gif) {
                    // Delete gif image file
                    if ($folder && $gif->path) {
                        $gifPath = public_path($gif->path);
                        if (file_exists($gifPath)) {
                            @unlink($gifPath);
                        }
                    }
                    $gif->delete();
                }
            }
            $version->delete();

            DB::commit();
            return response('', 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response($e->getMessage(), 500);
        }
    }
}
