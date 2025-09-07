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

class NewSocialController extends Controller
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
    public function show(newSocial $newSocial)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(newSocial $newSocial)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, newSocial $newSocial, $id)
    {
        DB::beginTransaction();
        try {
            $social = $newSocial->findOrFail($id);

            // Delete previous image file
            if ($social->path) {
                $oldPath = public_path($social->path);
                if (file_exists($oldPath)) {
                    @unlink($oldPath);
                }
            }

            // Delete the previous row
            $social->delete();

            // Create new social row if a new file is uploaded
            if ($request->hasFile('file')) {
                $file = $request->file('file');
                // Get preview name from related version
                $previewName = $social->version->feedbackSet->feedback->category->preview->name ?? 'social';
                $safeName = Str::slug($previewName) . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $uploadDir = public_path('uploads/socials');
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                $file->move($uploadDir, $safeName);

                // Create new row
                $newSocialRow = newSocial::create([
                    'name' => $safeName,
                    'version_id' => $social->version_id,
                    'position' => $request->input('position', 1),
                    'path' => 'uploads/socials/' . $safeName,
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
    public function destroy(newSocial $newSocial, $id)
    {
        DB::beginTransaction();
        try {
            $social = $newSocial->findOrFail($id);

            // Delete the file from the path
            if ($social->path) {
                $filePath = public_path($social->path);
                if (file_exists($filePath)) {
                    @unlink($filePath);
                }
            }

            // Delete the database record
            $social->delete();

            DB::commit();
            return response('', 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response($e->getMessage(), 500);
        }
    }
}
