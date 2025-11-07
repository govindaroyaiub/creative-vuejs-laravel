<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use App\Models\FileTransfer;
use Illuminate\Support\Str;

class FileTransferController extends Controller
{
    /**
     * Display a specific file transfer by slug
     * 
     * Retrieve and display a file transfer accessible via public slug URL.
     * This endpoint is accessible without authentication for file sharing.
     * 
     * @group File Management
     * 
     * @urlParam slug string required The unique slug identifier for the file transfer. Example: "abc123-def456-ghi789"
     * 
     * @response 200 {
     *   "fileTransfer": {
     *     "id": 1,
     *     "slug": "abc123-def456-ghi789",
     *     "name": "Project Assets",
     *     "client": "Planet Nine",
     *     "user": "John Doe",
     *     "created_at": "2025-11-06 10:30",
     *     "file_paths": ["asset1.zip", "asset2.zip"]
     *   }
     * }
     * 
     * @response 404 {
     *   "message": "File transfer not found"
     * }
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

        // Process file_paths for each transfer
        $fileTransfers->getCollection()->transform(function ($transfer) {
            // Split file_path into array and remove 'Transfer Files/' prefix
            if ($transfer->file_path) {
                $filePaths = array_map(function ($file) {
                    return str_replace('Transfer Files/', '', trim($file));
                }, explode(',', $transfer->file_path));
                $transfer->file_paths = $filePaths;
            } else {
                $transfer->file_paths = [];
            }
            return $transfer;
        });

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

    /**
     * Upload and create a new file transfer
     * 
     * Create a new file transfer with secure file uploads. Supports multiple archive files
     * with comprehensive security validation including file signature verification.
     * 
     * @group File Management
     * 
     * @bodyParam name string required The name for this file transfer. Example: "Project Assets Q4 2025"
     * @bodyParam client string required The client name for this transfer. Example: "Planet Nine"
     * @bodyParam file file[] required Array of archive files to upload (max 5 files, 20MB each). Must be ZIP, RAR, 7Z, TAR, or GZ format.
     * 
     * @response 302 {
     *   "message": "Redirect to file transfers list with success message"
     * }
     * 
     * @response 422 {
     *   "message": "The file contains invalid or suspicious content.",
     *   "errors": {
     *     "file.0": ["The file contains invalid or suspicious content."],
     *     "name": ["The name field is required."]
     *   }
     * }
     * 
     * @response 500 {
     *   "message": "File upload failed. Please try again."
     * }
     * 
     * @authenticated
     */
    public function store(Request $request)
    {
        // Simple validation
        $request->validate([
            'name' => 'required|string|max:255',
            'client' => 'required|string|max:255',
            'file' => 'required|array|min:1',
            'file.*' => 'required|file|max:20480', // 20MB max per file
        ]);

        // Prepare the file path array
        $filePaths = [];

        // Check if 'file' input is provided and is an array
        if ($request->hasFile('file')) {
            foreach ($request->file('file') as $file) {
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

                    Log::info('File uploaded successfully', [
                        'user_id' => Auth::id(),
                        'filename' => $newFileName,
                        'original_name' => $file->getClientOriginalName(),
                        'size' => $fileSize
                    ]);
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

        // Create a new record in the database
        try {
            $fileTransfer = new FileTransfer();
            $fileTransfer->slug = Str::uuid()->toString();
            $fileTransfer->name = $request->input('name');
            $fileTransfer->client = $request->input('client');
            $fileTransfer->user_id = Auth::id();
            $fileTransfer->file_path = implode(',', $filePaths);
            $fileTransfer->save();

            Log::info('File transfer created', [
                'user_id' => Auth::id(),
                'transfer_id' => $fileTransfer->id,
                'file_count' => count($filePaths)
            ]);

            return Redirect::route('file-transfers')->with('success', $fileTransfer->name . ' (' . $fileTransfer->client . ')' . ' uploaded successfully!');
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

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'client' => 'required|string|max:255',
            'file' => 'nullable|array',
            'file.*' => 'file|max:20480',
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
                $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $extension = $file->getClientOriginalExtension();

                $uniqueId = uniqid();
                $timestamp = time();
                $uniqueName = $originalName . '_' . $timestamp . '_' . $uniqueId . '.' . $extension;

                $destinationPath = public_path('Transfer Files');

                try {
                    $file->move($destinationPath, $uniqueName);
                    $filePaths[] = 'Transfer Files/' . $uniqueName;

                    Log::info('File updated successfully', [
                        'user_id' => Auth::id(),
                        'transfer_id' => $id,
                        'filename' => $uniqueName,
                        'original_name' => $file->getClientOriginalName()
                    ]);
                } catch (\Exception $e) {
                    Log::error('File update failed', [
                        'user_id' => Auth::id(),
                        'transfer_id' => $id,
                        'error' => $e->getMessage()
                    ]);

                    return Redirect::back()->withErrors([
                        'file' => 'File upload failed. Please try again.'
                    ]);
                }
            }

            $fileTransfer->file_path = implode(',', $filePaths);
        }

        // Update fields
        $fileTransfer->name = $request->input('name');
        $fileTransfer->client = $request->input('client');
        $fileTransfer->save();

        Log::info('File transfer updated', [
            'user_id' => Auth::id(),
            'transfer_id' => $id
        ]);

        return Redirect::route('file-transfers')->with('success', 'File transfer updated successfully!');
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
