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

class NewVideoController extends Controller
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
    public function show(newVideo $newVideo)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(newVideo $newVideo)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, newVideo $newVideo, $id)
    {
        $video = $newVideo->findOrFail($id);

        $request->validate([
            'size_id' => 'nullable|exists:video_sizes,id',
            'codec' => 'nullable|string|max:255',
            'aspect_ratio' => 'nullable|string|max:255',
            'fps' => 'nullable|string|max:255',
            'file' => 'nullable|file|mimes:mp4,webm',
            'companion_banner' => 'nullable|file|mimes:png,jpg,jpeg,gif',
        ]);

        // Update metadata
        $video->update([
            'size_id' => $request->input('size_id', $video->size_id),
            'codec' => $request->input('codec', $video->codec),
            'aspect_ratio' => $request->input('aspect_ratio', $video->aspect_ratio),
            'fps' => $request->input('fps', $video->fps),
        ]);

        // Handle video file replacement
        if ($request->hasFile('file')) {
            // Delete old video file
            if ($video->path) {
                $oldPath = public_path($video->path);
                if (file_exists($oldPath)) {
                    @unlink($oldPath);
                }
            }
            // Save new video file
            $file = $request->file('file');
            $previewName = $video->version->feedbackSet->feedback->category->preview->name ?? 'video';
            $safeName = Str::slug($previewName) . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $uploadDir = public_path('uploads/videos');
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

            $video->update([
                'path' => 'uploads/videos/' . $safeName,
                'file_size' => $fileSize,
            ]);
        }

        // Handle companion banner replacement
        if ($request->hasFile('companion_banner')) {
            // Delete old companion banner file
            if ($video->companion_banner_path) {
                $oldBannerPath = public_path($video->companion_banner_path);
                if (file_exists($oldBannerPath)) {
                    @unlink($oldBannerPath);
                }
            }
            // Save new companion banner file
            $banner = $request->file('companion_banner');
            $previewName = $video->version->feedbackSet->feedback->category->preview->name ?? 'companion_banner';
            $bannerName = Str::slug($previewName) . '_banner_' . uniqid() . '.' . $banner->getClientOriginalExtension();
            $uploadDir = public_path('uploads/videos');
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            $banner->move($uploadDir, $bannerName);

            $video->update([
                'companion_banner_path' => 'uploads/videos/' . $bannerName,
            ]);
        }

        return response('', 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(newVideo $newVideo, $id)
    {
        $video = $newVideo->findOrFail($id);

        // Delete video file
        if ($video->path) {
            $videoPath = public_path($video->path);
            if (file_exists($videoPath)) {
                @unlink($videoPath);
            }
        }

        // Delete companion banner file
        if ($video->companion_banner_path) {
            $bannerPath = public_path($video->companion_banner_path);
            if (file_exists($bannerPath)) {
                @unlink($bannerPath);
            }
        }

        // Delete database row
        $video->delete();

        return response('', 200);
    }

    public function deleteCompanionBanner(Request $request, newVideo $newVideo, $id)
    {
        $video = $newVideo->findOrFail($id);

        // Delete companion banner file
        if ($video->companion_banner_path) {
            $bannerPath = public_path($video->companion_banner_path);
            if (file_exists($bannerPath)) {
                @unlink($bannerPath);
            }
        }

        $video->update([
            'companion_banner_path' => null
        ]);

        return response('', 200);
    }
}
