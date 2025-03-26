<?php

namespace App\Http\Controllers;
use App\Models\FileTransfer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class FileTransferController extends Controller
{
    /**
     * Store the file transfer data.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function storeTransferFiles(Request $request)
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
        $fileTransfer->name = $request->input('name');
        $fileTransfer->client = $request->input('client');
        $fileTransfer->user_id = Auth::id(); // Assuming the user ID is stored in the 'id' column
        $fileTransfer->file_path = implode(',', $filePaths); // Store paths as a comma-separated string
        $fileTransfer->save();

        // Return response (could be a redirect or success message)
        return redirect()->route('file-transfers')->with('success', 'Files uploaded successfully!');
    }

    public function destroyTransferFiles($id)
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
                    } else {
                        // Optionally log if the file does not exist
                        \Log::warning('File not found for deletion: ' . $fullPath);
                    }
                }
            }
        } else {
            // Handle the case when file_path is null or empty
            // You can throw an exception, log a message, or return an error response
            // Example: 
            \Log::error('File path is missing for file transfer ID ' . $id);
        }

        // After deleting the files, delete the database record
        $fileTransfer->delete();
    }
}
