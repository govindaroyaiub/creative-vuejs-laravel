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
    public function index() {

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
    public function show(newFeedback $newFeedback)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $feedback = newFeedback::findOrFail($id);
        $preview = $feedback->category->preview;

        return Inertia::render('Previews/Categories/Feedbacks/Edit', [
            'feedback' => $feedback,
            'description' => $feedback->description,
            'preview' => $preview
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, newFeedback $newFeedback, $id)
    {
        $feedback = newFeedback::findOrFail($id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $feedback->update($validated);

        $preview = $feedback->category->preview; // load the associated preview for props

        return Inertia::render('Previews/Categories/Feedbacks/Edit', [
            'feedback' => $feedback,
            'preview' => $preview,
            'flash' => ['success' => 'Feedback updated successfully!'],
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(newFeedback $newFeedback)
    {
        //
    }
}
