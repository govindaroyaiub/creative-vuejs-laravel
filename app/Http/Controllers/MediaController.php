<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Media;
use App\Models\User;
use Inertia\Inertia;

class MediaController extends Controller
{
    public function index()
    {
        $medias = Media::with('uploader')->orderBy('created_at', 'asc')->paginate(10); // or whatever per page
        return Inertia::render('Medias/Index', [
            'medias' => $medias,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'file' => 'required|file|max:10240', // 10MB max
        ]);

        $file = $request->file('file');
        $filename = time() . '-' . $file->getClientOriginalName();
        $path = $file->move(public_path('uploads/media'), $filename);

        Media::create([
            'name' => $request->name,
            'path' => 'uploads/media/' . $filename,
            'uploader_id' => auth()->id(),
        ]);

        return redirect()->route('medias')->with('success', 'Media uploaded successfully.');
    }

    public function destroy($id)
    {
        $media = Media::findOrFail($id);
        $filePath = public_path($media->path);
        if (file_exists($filePath)) {
            unlink($filePath);
        }

        $media->delete();

        return redirect()->route('medias')->with('success', 'Media deleted successfully.');
    }

    public function download($id)
    {
        $media = Media::findOrFail($id);
        $path = public_path($media->path);

        if (!file_exists($path)) {
            abort(404);
        }

        return response()->download($path, $media->name . '.' . pathinfo($media->path, PATHINFO_EXTENSION));
    }
}
