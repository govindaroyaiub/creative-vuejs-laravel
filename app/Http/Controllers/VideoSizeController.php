<?php

namespace App\Http\Controllers;

use App\Models\VideoSize;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

class VideoSizeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = VideoSize::query();

        if ($search = $request->input('search')) {
            $query->where('name', 'like', "%{$search}%")
                ->orWhere('width', 'like', "%{$search}%")
                ->orWhere('height', 'like', "%{$search}%");
        }

        $videoSizes = $query->paginate(10)->withQueryString();

        return Inertia::render('VideoSizes/Index', [
            'videoSizes' => $videoSizes,
        ]);
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
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'width' => 'required|integer|min:1',
            'height' => 'required|integer|min:1',
        ]);

        // Check if combination exists
        $exists = VideoSize::where('name', $validated['name'])
            ->where('width', $validated['width'])
            ->where('height', $validated['height'])
            ->exists();

        if ($exists) {
            return Redirect::route('video-sizes-index')
                ->with('error', 'Sorry! This video size already exists.');
        }

        // Create new video size
        VideoSize::create($validated);

        return Redirect::route('video-sizes-index')
            ->with('success', 'Video size added successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(VideoSize $videoSize)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(VideoSize $videoSize)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, VideoSize $videoSize, $id)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'width' => 'required|integer|min:1',
            'height' => 'required|integer|min:1',
        ]);

        // Check if another record has same name + dimensions
        $exists = VideoSize::where('name', $validated['name'])
            ->where('width', $validated['width'])
            ->where('height', $validated['height'])
            ->where('id', '!=', $id)
            ->exists();

        if ($exists) {
            return Redirect::route('video-sizes-index')->with('error', 'Sorry! This video size already exists!');
        }

        $videoSize = VideoSize::findOrFail($id);
        $videoSize->update($validated);

        return Redirect::route('video-sizes-index')->with('success', 'Video size updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $videoSize = VideoSize::find($id);

        if (! $videoSize) {
            return response()->json(['message' => 'Video size not found.'], 404);
        }
        $videoSize->delete();
    }
}
