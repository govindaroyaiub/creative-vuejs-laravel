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
        // Enhanced validation with stricter rules
        $request->validate([
            'name' => 'required|string|max:255|regex:/^[a-zA-Z0-9\s\-_.]+$/', // Alphanumeric with safe chars only
            'client' => 'required|string|max:255|regex:/^[a-zA-Z0-9\s\-_.]+$/',
            'file' => 'required|array|min:1|max:5', // Limit to 5 files max
            'file.*' => [
                'file',
                'mimes:zip,rar,7z,tar,gz', // Only archive formats
                'max:20480', // 20MB max per file
                function ($attribute, $value, $fail) {
                    // Additional custom validation
                    if (!$this->validateArchiveFile($value)) {
                        $fail('The file contains invalid or suspicious content.');
                    }
                },
            ],
        ]);

        // Prepare the file path array
        $filePaths = [];
        $totalSize = 0;

        // Check if 'file' input is provided and is an array
        if ($request->hasFile('file')) {
            foreach ($request->file('file') as $file) {
                $totalSize += $file->getSize();

                // Check total upload size (max 50MB combined)
                if ($totalSize > 52428800) {
                    return response()->json([
                        'message' => 'Total file size exceeds the 50MB limit.',
                    ], 422);
                }

                $originalName = $this->sanitizeFilename(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
                $extension = strtolower($file->getClientOriginalExtension());

                // Generate a cryptographically secure unique name
                $uniqueId = bin2hex(random_bytes(16));
                $timestamp = time();
                $newFileName = $originalName . '_' . $timestamp . '_' . $uniqueId . '.' . $extension;

                $destinationPath = public_path('Transfer Files');

                // Ensure directory exists with secure permissions
                if (!file_exists($destinationPath)) {
                    mkdir($destinationPath, 0755, true);

                    // Create .htaccess for additional security
                    $htaccessContent = "# Deny access to PHP files\n<Files \"*.php\">\nOrder Allow,Deny\nDeny from all\n</Files>\n\n# Disable script execution\nAddHandler cgi-script .php .phtml .php3 .php4 .php5 .phar\nOptions -ExecCGI";
                    file_put_contents($destinationPath . '/.htaccess', $htaccessContent);
                }

                try {
                    $file->move($destinationPath, $newFileName);
                    $filePaths[] = 'Transfer Files/' . $newFileName;

                    Log::info('File uploaded successfully', [
                        'user_id' => Auth::id(),
                        'filename' => $newFileName,
                        'original_name' => $file->getClientOriginalName(),
                        'size' => $file->getSize()
                    ]);
                } catch (\Exception $e) {
                    Log::error('File upload failed', [
                        'user_id' => Auth::id(),
                        'filename' => $newFileName,
                        'error' => $e->getMessage()
                    ]);

                    return response()->json([
                        'message' => 'File upload failed. Please try again.',
                    ], 500);
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

            return response()->json([
                'message' => 'Failed to create file transfer record.',
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255|regex:/^[a-zA-Z0-9\s\-_.]+$/',
            'client' => 'required|string|max:255|regex:/^[a-zA-Z0-9\s\-_.]+$/',
            'file' => 'nullable|array|max:5',
            'file.*' => [
                'file',
                'mimes:zip,rar,7z,tar,gz',
                'max:20480',
                function ($attribute, $value, $fail) {
                    if (!$this->validateArchiveFile($value)) {
                        $fail('The file contains invalid or suspicious content.');
                    }
                },
            ],
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

            // Upload new files with enhanced security
            $filePaths = [];
            $totalSize = 0;

            foreach ($request->file('file') as $file) {
                $totalSize += $file->getSize();

                if ($totalSize > 52428800) { // 50MB total limit
                    return response()->json([
                        'message' => 'Total file size exceeds the 50MB limit.',
                    ], 422);
                }

                $originalName = $this->sanitizeFilename(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
                $extension = strtolower($file->getClientOriginalExtension());

                $uniqueId = bin2hex(random_bytes(16));
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

                    return response()->json([
                        'message' => 'File upload failed. Please try again.',
                    ], 500);
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

        return response()->json([
            'message' => 'File transfer updated successfully!',
            'file_paths' => array_map(function ($path) {
                return str_replace('Transfer Files/', '', $path);
            }, explode(',', $fileTransfer->file_path)),
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

    /**
     * Validate archive file for suspicious content
     */
    private function validateArchiveFile($file): bool
    {
        try {
            // Check file size (already handled in validation, but double-check)
            if ($file->getSize() > 20971520) { // 20MB
                return false;
            }

            // Read first few bytes to check for archive signatures
            $handle = fopen($file->getRealPath(), 'rb');
            if (!$handle) {
                return false;
            }

            $bytes = fread($handle, 32);
            fclose($handle);

            $hex = strtoupper(bin2hex($bytes));

            // Check for valid archive signatures
            $validSignatures = [
                '504B0304', // ZIP
                '504B0506', // ZIP (empty)
                '504B0708', // ZIP (spanned)
                '526172211A07', // RAR
                '377ABCAF271C', // 7Z
                '1F8B08', // GZIP
                '425A68', // BZIP2
            ];

            foreach ($validSignatures as $signature) {
                if (strpos($hex, $signature) === 0) {
                    return true;
                }
            }

            return false;
        } catch (\Exception $e) {
            Log::error('Archive validation error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Sanitize filename to prevent directory traversal and other attacks
     */
    private function sanitizeFilename(string $filename): string
    {
        // Remove any path separators and dangerous characters
        $filename = basename($filename);
        $filename = preg_replace('/[^a-zA-Z0-9\-_.]/', '_', $filename);

        // Remove multiple consecutive underscores/dots
        $filename = preg_replace('/[_.]{2,}/', '_', $filename);

        // Ensure filename is not too long
        if (strlen($filename) > 100) {
            $filename = substr($filename, 0, 100);
        }

        // Ensure filename is not empty or just dots/underscores
        if (empty(trim($filename, '._'))) {
            $filename = 'file_' . time();
        }

        return $filename;
    }
}
