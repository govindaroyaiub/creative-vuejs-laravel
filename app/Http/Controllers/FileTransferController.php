<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\File;
use Inertia\Inertia;
use App\Models\FileTransfer;
use Illuminate\Support\Str;

class FileTransferController extends Controller
{
    /**
     * Store the file transfer data.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */

    public function show($slug)
    {
        $fileTransfer = FileTransfer::with('user:id,name')->where('slug', $slug)->firstOrFail();

        // Remove 'Transfer Files' from each file path, then split by commas
        $filePaths = array_map(function ($file) {
            return str_replace('Transfer Files/', '', $file);
        }, explode(',', $fileTransfer->file_path)); // Split the file paths into an array

        return Inertia::render('FileTransfers/View', [
            'fileTransfer' => [
                'slug' => $fileTransfer->slug,
                'id' => $fileTransfer->id,
                'name' => $fileTransfer->name,
                'client' => $fileTransfer->client,
                'user' => $fileTransfer->user ? $fileTransfer->user->name : 'Unknown',
                'created_at' => $fileTransfer->created_at->format('Y-m-d H:i'),
                'file_paths' => $filePaths, // Send as an array
            ]
        ]);
    }

    public function index(Request $request)
    {
        $query = FileTransfer::with('user:id,name');

        if ($search = $request->input('search')) {
            $query->where('name', 'like', "%{$search}%")
                ->orWhere('client', 'like', "%{$search}%")
                ->orWhereRaw("DATE_FORMAT(created_at, '%d %M %Y') LIKE ?", ["%{$search}%"]);
        }

        $fileTransfers = $query->latest()->paginate(10)->withQueryString();

        return Inertia::render('FileTransfers/Index', [
            'fileTransfers' => $fileTransfers,
            'search' => $search,
        ]);
    }

    public function create()
    {
        return Inertia::render('FileTransfers/Create');
    }

    public function edit($id)
    {
        $fileTransfer = FileTransfer::with('user:id,name')->findOrFail($id);

        // Remove 'Transfer Files' from each file path, then split by commas
        $filePaths = array_map(function ($file) {
            return str_replace('Transfer Files/', '', $file);
        }, explode(',', $fileTransfer->file_path)); // Split the file paths into an array

        return Inertia::render('FileTransfers/Edit', [
            'fileTransfer' => [
                'id' => $fileTransfer->id,
                'name' => $fileTransfer->name,
                'client' => $fileTransfer->client,
                'user' => $fileTransfer->user ? $fileTransfer->user->name : 'Unknown',
                'created_at' => $fileTransfer->created_at->format('Y-m-d H:i'),
                'file_paths' => $filePaths, // Send as an array
            ]
        ]);
    }

    public function store(Request $request)
    {
        // Validate the request
        $request->validate([
            'name' => 'required|string|max:255',
            'client' => 'required|string|max:255',
            'file' => 'required|array|min:1', // Multiple files allowed
            'file.*' => 'mimes:zip|max:10240', // Only zip files and max 10MB each
        ]);

        // Prepare the file path array
        $filePaths = [];

        // Check if 'file' input is provided and is an array
        if ($request->hasFile('file')) {
            foreach ($request->file('file') as $file) {
                $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $extension = $file->getClientOriginalExtension();

                // Generate a unique name using timestamp & random string
                $uniqueId = uniqid();
                $newFileName = $originalName . '_' . $uniqueId . '.' . $extension;

                $destinationPath = public_path('Transfer Files');
                if (!file_exists($destinationPath)) {
                    mkdir($destinationPath, 0777, true);
                }

                $file->move($destinationPath, $newFileName);

                $filePaths[] = 'Transfer Files/' . $newFileName;
            }
        }

        // Create a new record in the database
        $fileTransfer = new FileTransfer();
        $fileTransfer->slug = Str::uuid()->toString(); // Generate a unique slug
        $fileTransfer->name = $request->input('name');
        $fileTransfer->client = $request->input('client');
        $fileTransfer->user_id = Auth::id(); // Assuming the user ID is stored in the 'id' column
        $fileTransfer->file_path = implode(',', $filePaths); // Store paths as a comma-separated string
        $fileTransfer->expires_at = now()->addDays(30); // Set expiration date to 7 days from now
        $fileTransfer->save();

        // Return response (could be a redirect or success message)
        return Redirect::route('file-transfers')->with('success', $fileTransfer->name . ' (' . $fileTransfer->client . ')' . ' uploaded successfully!');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'client' => 'required|string|max:255',
            'file' => 'nullable|array',
            'file.*' => 'mimes:zip|max:10240',
        ]);

        $fileTransfer = FileTransfer::findOrFail($id);

        // Delete old files if new ones are uploaded
        if ($request->hasFile('file')) {
            $oldFiles = explode(',', $fileTransfer->file_path);
            foreach ($oldFiles as $oldFile) {
                $filePath = public_path($oldFile);
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }

            // Upload new files
            $filePaths = [];
            foreach ($request->file('file') as $file) {
                $uniqueName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME) . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $destinationPath = public_path('Transfer Files');
                $file->move($destinationPath, $uniqueName);
                $filePaths[] = 'Transfer Files/' . $uniqueName;
            }

            $fileTransfer->file_path = implode(',', $filePaths);
        }

        // Update fields
        $fileTransfer->name = $request->input('name');
        $fileTransfer->client = $request->input('client');
        $fileTransfer->save();

        return response()->json([
            'message' => 'File transfer updated successfully!',
            'file_paths' => array_map(function ($path) {
                // Remove "Transfer Files/" from each path
                return str_replace('Transfer Files/', '', $path);
            }, explode(',', $fileTransfer->file_path)), // Split file paths and apply transformation
        ]);
    }

    public function destroy($id)
    {
        $fileTransfer = FileTransfer::findOrFail($id);

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
    }
}
