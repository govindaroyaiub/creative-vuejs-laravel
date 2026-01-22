<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Media;
use App\Models\User;
use Inertia\Inertia;

class MediaController extends Controller
{
    public function index(Request $request)
    {
        $query = Media::with('uploader');

        if ($search = $request->input('search')) {
            $query->where('name', 'like', "%{$search}%");
        }

        $medias = $query->orderBy('created_at', 'asc')->paginate(10)->withQueryString();

        return Inertia::render('Medias/Index', [
            'medias' => $medias,
            'search' => $search,
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

    public function bulkDestroy(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:media,id',
        ]);

        $ids = $request->input('ids');

        $deleted = [];
        $skipped = [];

        $medias = Media::whereIn('id', $ids)->get();

        foreach ($medias as $media) {
            $filePath = public_path($media->path);

            if ($media->path && file_exists($filePath)) {
                try {
                    unlink($filePath);
                } catch (\Exception $e) {
                    \Log::warning('Failed to unlink media file in bulkDestroy', ['file' => $filePath, 'error' => $e->getMessage()]);
                    $skipped[] = ['id' => $media->id, 'reason' => 'unlink_failed'];
                    continue;
                }
            }

            try {
                $media->delete();
                $deleted[] = $media->id;
            } catch (\Exception $e) {
                \Log::error('Failed to delete Media record in bulkDestroy', ['id' => $media->id, 'error' => $e->getMessage()]);
                $skipped[] = ['id' => $media->id, 'reason' => 'delete_failed'];
            }
        }
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
