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

class NewFeedbackController extends Controller
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
    public function show(newFeedback $newFeedback)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id) {}

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, newFeedback $newFeedback, $id) {}

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(newFeedback $newFeedback, $id)
    {
        DB::beginTransaction();
        try {
            // Get the feedback to delete
            $feedback = $newFeedback->findOrFail($id);

            // Get the category type for folder deletion
            $category = $feedback->category;
            $folderMap = [
                'banner' => 'uploads/banners',
                'video' => 'uploads/videos',
                'gif' => 'uploads/gifs',
                'social' => 'uploads/socials',
            ];
            $folder = $folderMap[$category->type] ?? null;

            // Delete all related sets, versions, banners, and files
            foreach ($feedback->feedbackSets as $set) {
                foreach ($set->versions as $version) {
                    if ($category->type === 'banner') {
                        foreach ($version->banners as $banner) {
                            // Delete banner folder/file based on category type
                            if ($folder && $banner->path) {
                                $bannerPath = public_path($banner->path);
                                if (is_dir($bannerPath)) {
                                    \Illuminate\Support\Facades\File::deleteDirectory($bannerPath);
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
                $set->delete();
            }

            // Store if this feedback was active
            $wasActive = $feedback->is_active == 1;
            $categoryId = $feedback->category_id;

            // Delete the feedback itself
            $feedback->delete();

            // If deleted feedback was active, set last feedback as active (if any)
            if ($wasActive) {
                $lastFeedback = newFeedback::where('category_id', $categoryId)
                    ->orderByDesc('id')
                    ->first();
                if ($lastFeedback) {
                    $lastFeedback->is_active = 1;
                    $lastFeedback->save();
                }
            }

            DB::commit();
            return response('', 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response($e->getMessage(), 500);
        }
    }
}
