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
use App\Models\FileTransfer;
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

    public function approve(newFeedback $newFeedback, $id, Request $request)
    {
        try {
            // Get the feedback to approve
            $feedback = $newFeedback->findOrFail($id);
            $category = $feedback->category;

            $totalApprovedFeedbacks = newFeedback::where('category_id', $category->id)
                ->where('is_approved', true)
                ->count();

            if ($totalApprovedFeedbacks != 0) {
                return response('Only one approved feedback is allowed per category.', 400);
            } else {
                $filePaths = [];
                if ($request->hasFile('files')) {
                    foreach ($request->file('files') as $file) {
                        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                        $extension = $file->getClientOriginalExtension();
                        $fileSize = $file->getSize(); // Get size before moving

                        // Generate a unique name
                        $uniqueId = uniqid();
                        $timestamp = time();
                        $newFileName = $originalName . '_' . $timestamp . '_' . $uniqueId . '.' . $extension;

                        $destinationPath = public_path('Transfer Files');

                        // Ensure directory exists
                        if (!file_exists($destinationPath)) {
                            mkdir($destinationPath, 0755, true);
                        }

                        try {
                            $file->move($destinationPath, $newFileName);
                            $filePaths[] = 'Transfer Files/' . $newFileName;
                        } catch (\Exception $e) {
                            Log::error('File upload failed', [
                                'user_id' => Auth::id(),
                                'filename' => $newFileName,
                                'error' => $e->getMessage()
                            ]);

                            return Redirect::back()->withErrors([
                                'file' => 'File upload failed. Please try again.'
                            ]);
                        }
                    }
                }
                try {
                    $fileTransfer = new FileTransfer();
                    $fileTransfer->slug = Str::uuid()->toString();
                    $fileTransfer->name = $request->input('transfer_name');
                    $fileTransfer->client = $request->input('client_name');
                    $fileTransfer->user_id = Auth::id();
                    $fileTransfer->file_path = implode(',', $filePaths);
                    $fileTransfer->save();

                    newCategory::where('id', $feedback->category_id)->update(['file_transfer_id' => $fileTransfer->id]);

                    $feedback->is_approved = true;
                    $feedback->save();

                    return response('', 200);
                } catch (\Exception $e) {
                    // Clean up uploaded files if database save fails
                    foreach ($filePaths as $filePath) {
                        $fullPath = public_path($filePath);
                        if (file_exists($fullPath)) {
                            unlink($fullPath);
                        }
                    }

                    Log::error('File transfer creation failed', [
                        'user_id' => Auth::id(),
                        'error' => $e->getMessage()
                    ]);

                    return Redirect::back()->withErrors([
                        'file' => 'Failed to create file transfer record.'
                    ]);
                }
            }
        } catch (\Exception $e) {
            return response($e->getMessage(), 500);
        }
    }

    public function disapprove(newFeedback $newFeedback, $id)
    {
        try {
            // Get the feedback to disapprove
            $feedback = $newFeedback->findOrFail($id);
            $category = $feedback->category;

            $fileTransferId = $category->file_transfer_id;
            $fileTransfer = FileTransfer::findOrFail($fileTransferId);

            // Check if file_path is not null or empty
            if ($fileTransfer->file_path) {
                // Assuming 'file_path' is a string (no need for json_decode)
                $filePaths = is_array($fileTransfer->file_path) ? $fileTransfer->file_path : explode(',', $fileTransfer->file_path);

                // Make sure filePaths is an array before looping through it
                if (is_array($filePaths)) {
                    // Loop through each file and delete
                    foreach ($filePaths as $filePath) {
                        // Construct the full path, prefixing with 'public/' and using public_path()
                        $fullPath = public_path($filePath);

                        // Check if the file exists and delete it
                        if (file_exists($fullPath)) {
                            unlink($fullPath); // Delete the file
                        }
                    }
                }
                // After deleting the files, delete the database record
                $fileTransfer->delete();
            }

            newCategory::where('id', $feedback->category_id)->update(['file_transfer_id' => null]);
            $feedback->is_approved = false;
            $feedback->save();
            return response('', 200);
        } catch (\Exception $e) {
            return response($e->getMessage(), 500);
        }
    }
}
