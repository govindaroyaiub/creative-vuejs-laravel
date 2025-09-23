<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use ZipArchive;
use Tinify\Tinify;

class TinyPngController extends Controller
{
    public function __construct()
    {
        Tinify::setKey(config('services.tinypng.key'));
    }

    public function index()
    {
        return Inertia::render('TinyPng/Index');
    }

    public function compress(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:png,jpg,jpeg|max:5120',
        ]);

        try {
            // Set TinyPNG API key
            \Tinify\Tinify::setKey(config('services.tinypng.key'));

            $file = $request->file('image');
            $originalSize = $file->getSize();

            // Create unique filename
            $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
            $tempPath = storage_path('app/temp/tinypng');

            if (!is_dir($tempPath)) {
                mkdir($tempPath, 0755, true);
            }

            $originalPath = $tempPath . '/original_' . $filename;
            $compressedPath = $tempPath . '/compressed_' . $filename;

            // Save original file
            $file->move(dirname($originalPath), basename($originalPath));

            // Compress with TinyPNG using correct namespace
            $source = \Tinify\fromFile($originalPath);
            $source->toFile($compressedPath);

            $compressedSize = filesize($compressedPath);
            $savingsPercent = round((($originalSize - $compressedSize) / $originalSize) * 100, 1);

            // Store in session
            $sessionKey = 'tinypng_files';
            $files = session($sessionKey, []);
            $files[] = [
                'original_path' => $originalPath,
                'compressed_path' => $compressedPath,
                'original_name' => $file->getClientOriginalName(),
                'compressed_name' => 'compressed_' . $file->getClientOriginalName(),
            ];
            session([$sessionKey => $files]);

            // Cleanup
            if (file_exists($originalPath)) {
                unlink($originalPath);
            }

            return response()->json([
                'success' => true,
                'compressed_size' => $compressedSize,
                'original_size' => $originalSize,
                'savings_percent' => $savingsPercent,
            ]);
        } catch (\Tinify\AccountException $e) {
            return response()->json([
                'success' => false,
                'message' => 'TinyPNG API key issue: ' . $e->getMessage(),
            ], 422);
        } catch (\Tinify\ClientException $e) {
            return response()->json([
                'success' => false,
                'message' => 'TinyPNG request error: ' . $e->getMessage(),
            ], 422);
        } catch (\Tinify\ServerException $e) {
            return response()->json([
                'success' => false,
                'message' => 'TinyPNG server error: ' . $e->getMessage(),
            ], 422);
        } catch (\Tinify\ConnectionException $e) {
            return response()->json([
                'success' => false,
                'message' => 'TinyPNG connection error: ' . $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'General error: ' . $e->getMessage(),
            ], 422);
        }
    }

    public function downloadZip()
    {
        try {
            $files = session('tinypng_files', []);

            if (empty($files)) {
                return response()->json(['error' => 'No files to download'], 422);
            }

            $zipPath = storage_path('app/temp/tinified_' . time() . '.zip');
            $zip = new ZipArchive();

            if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
                return response()->json(['error' => 'Could not create zip file'], 500);
            }

            foreach ($files as $file) {
                if (file_exists($file['compressed_path'])) {
                    $zip->addFile($file['compressed_path'], $file['compressed_name']);
                }
            }

            $zip->close();

            // Cleanup
            foreach ($files as $file) {
                if (file_exists($file['compressed_path'])) {
                    unlink($file['compressed_path']);
                }
            }
            session()->forget('tinypng_files');

            return response()->download($zipPath, 'tinified.zip')->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create ZIP: ' . $e->getMessage(),
            ], 500);
        }
    }
}
