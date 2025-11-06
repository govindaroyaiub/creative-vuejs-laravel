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
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;

class NewCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(newPreview $preview) {}


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
    public function show(newCategory $newCategory)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(newCategory $newCategory, $id) {}

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, newCategory $newCategory, $id) {}

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(newCategory $newCategory, $id)
    {
        DB::beginTransaction();
        try {
            // Get the category to delete
            $category = $newCategory->findOrFail($id);

            // Delete all related feedbacks, sets, versions, banners, and files
            // Delete category folder based on type
            $folderMap = [
                'banner' => 'uploads/banners',
                'video' => 'uploads/videos',
                'gif' => 'uploads/gifs',
                'social' => 'uploads/socials',
            ];
            $folder = $folderMap[$category->type] ?? null;
            if ($folder) {
                // If you store category folders by name or id, adjust this path
                $categoryFolder = $folder . '/' . $category->id;
                $fullPath = public_path($categoryFolder);
                if (is_dir($fullPath)) {
                    File::deleteDirectory($fullPath);
                }
            }

            foreach ($category->feedbacks as $feedback) {
                foreach ($feedback->feedbackSets as $set) {
                    foreach ($set->versions as $version) {
                        if ($category->type === 'banner') {
                            foreach ($version->banners as $banner) {
                                // Delete banner folder/file
                                if ($banner->path) {
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
                        if ($category->type === 'gif') {
                            foreach ($version->gifs as $gif) {
                                // Delete gif image file
                                if ($gif->path) {
                                    $gifPath = public_path($gif->path);
                                    if (file_exists($gifPath)) {
                                        @unlink($gifPath);
                                    }
                                }
                                $gif->delete();
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
                        $version->delete();
                    }
                    $set->delete();
                }
                $feedback->delete();
            }

            // Store if this category was active
            $wasActive = $category->is_active == 1;

            // Delete the category itself
            $category->delete();

            // If deleted category was active, set last category as active
            if ($wasActive) {
                $lastCategory = newCategory::where('preview_id', $category->preview_id)
                    ->orderByDesc('id')
                    ->first();
                if ($lastCategory) {
                    $lastCategory->is_active = 1;
                    $lastCategory->save();
                }
            }

            DB::commit();
            return redirect()->route('previews.update.all', $category->preview_id)->with('success', 'Category and all related data deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
