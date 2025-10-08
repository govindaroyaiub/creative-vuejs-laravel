<?php

namespace App\Http\Controllers;

use App\Services\CompressionCountService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use ZipArchive;
use Tinify\Tinify;

class TinyPngController extends Controller
{
    private CompressionCountService $compressionService;

    public function __construct(CompressionCountService $compressionService)
    {
        $this->compressionService = $compressionService;

        // Only set the key when we actually need to use TinyPNG
        if (config('services.tinypng.key')) {
            Tinify::setKey(config('services.tinypng.key'));
        }
    }

    public function index()
    {
        // Get local compression data instead of calling TinyPNG API
        $compressionData = $this->compressionService->getCompressionData();

        return Inertia::render('TinyPng/Index', [
            'compressionCount' => $compressionData['count'],
            'remainingCount' => $compressionData['remaining'],
            'currentMonth' => $compressionData['month'],
            'isNewAccount' => false // We don't need this anymore
        ]);
    }

    public function compressionCount()
    {
        $compressionData = $this->compressionService->getCompressionData();

        return response()->json([
            'compression_count' => $compressionData['count'],
            'remaining' => $compressionData['remaining'],
            'current_month' => $compressionData['month'],
            'is_free_tier' => true, // Always true since we're tracking locally
            'message' => "Local tracking: {$compressionData['count']}/500 compressions used this month"
        ]);
    }

    public function compress(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:png,jpg,jpeg|max:5120',
        ]);

        // Check if user has remaining compressions
        if (!$this->compressionService->hasRemainingCompressions()) {
            return response()->json([
                'success' => false,
                'message' => 'Monthly compression limit reached (500/500). Please wait for next month or upgrade to paid plan.',
            ], 422);
        }

        // Check API key
        $apiKey = config('services.tinypng.key');
        if (!$apiKey) {
            return response()->json([
                'success' => false,
                'message' => 'TinyPNG API key not configured. Please add TINYPNG_API_KEY to your .env file.',
            ], 422);
        }

        try {
            \Tinify\Tinify::setKey($apiKey);

            $file = $request->file('image');
            $originalSize = $file->getSize();

            $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
            $tempPath = storage_path('app/temp/tinypng');

            if (!is_dir($tempPath)) {
                mkdir($tempPath, 0755, true);
            }

            $originalPath = $tempPath . '/original_' . $filename;
            $compressedPath = $tempPath . '/compressed_' . $filename;

            $file->move(dirname($originalPath), basename($originalPath));

            // Perform compression
            $source = \Tinify\fromFile($originalPath);
            $source->toFile($compressedPath);

            $compressedSize = filesize($compressedPath);
            $savingsPercent = round((($originalSize - $compressedSize) / $originalSize) * 100, 1);

            // ✅ Increment our local counter after successful compression
            $newCount = $this->compressionService->incrementCount();

            // Store in session for ZIP download
            $sessionKey = 'tinypng_files';
            $files = session($sessionKey, []);
            $files[] = [
                'original_path' => $originalPath,
                'compressed_path' => $compressedPath,
                'original_name' => $file->getClientOriginalName(),
                'compressed_name' => 'compressed_' . $file->getClientOriginalName(),
            ];
            session([$sessionKey => $files]);

            if (file_exists($originalPath)) {
                unlink($originalPath);
            }

            return response()->json([
                'success' => true,
                'compressed_size' => $compressedSize,
                'original_size' => $originalSize,
                'savings_percent' => $savingsPercent,
                'compression_count' => $newCount,
                'remaining_count' => 500 - $newCount,
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

    /**
     * Admin route to reset compression count
     */
    public function resetCount()
    {
        $this->compressionService->resetCount();

        return response()->json([
            'success' => true,
            'message' => 'Compression count has been reset to 0'
        ]);
    }
}
