<?php

namespace App\Http\Controllers;

use App\Models\VideoSize;
use Illuminate\Http\Request;
use Inertia\Inertia;

class VideoSizeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $videoSizes = VideoSize::orderBy('name', 'ASC')->paginate(10);
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
        //
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
    public function update(Request $request, VideoSize $videoSize)
    {
        //
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
