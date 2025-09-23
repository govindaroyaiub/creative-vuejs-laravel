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
        // Get current compression count
        $compressionData = $this->getCompressionData();

        return Inertia::render('TinyPng/Index', [
            'compressionCount' => $compressionData['count'],
            'isNewAccount' => $compressionData['isNew']
        ]);
    }

    private function getCompressionData()
    {
        try {
            // Check if API key is configured
            $apiKey = config('services.tinypng.key');
            if (!$apiKey || strlen($apiKey) < 20) {
                return [
                    'count' => null,
                    'isNew' => false,
                    'message' => 'TinyPNG API key not configured or invalid format.'
                ];
            }

            \Tinify\Tinify::setKey($apiKey);
            $count = \Tinify\Tinify::getCompressionCount();

            // IMPORTANT: TinyPNG behavior explanation:
            // - getCompressionCount() returns NULL when you're on the FREE plan (500 free compressions)
            // - getCompressionCount() returns a NUMBER when you're on a PAID plan
            // - This means NULL doesn't mean "0 used" - it means "still in free tier, unknown usage"

            if ($count === null) {
                return [
                    'count' => null, // Keep as null to indicate unknown free tier usage
                    'isNew' => false, // Not necessarily new, just free tier
                    'message' => 'Free plan active - 500 compressions available. Usage tracking starts with paid plan.'
                ];
            }

            return [
                'count' => $count,
                'isNew' => false,
                'message' => 'Paid plan active - API usage retrieved successfully.'
            ];
        } catch (\Tinify\AccountException $e) {
            return [
                'count' => null,
                'isNew' => false,
                'message' => 'Invalid API key: ' . $e->getMessage()
            ];
        } catch (\Tinify\ClientException $e) {
            return [
                'count' => null,
                'isNew' => false,
                'message' => 'API request error: ' . $e->getMessage()
            ];
        } catch (\Tinify\ConnectionException $e) {
            return [
                'count' => null,
                'isNew' => false,
                'message' => 'Connection error: ' . $e->getMessage()
            ];
        } catch (\Exception $e) {
            return [
                'count' => null,
                'isNew' => false,
                'message' => 'API error: ' . $e->getMessage()
            ];
        }
    }

    public function compressionCount()
    {
        $compressionData = $this->getCompressionData();

        return response()->json([
            'compression_count' => $compressionData['count'],
            'remaining' => null, // Can't calculate remaining for free tier
            'is_new_account' => $compressionData['isNew'],
            'is_free_tier' => $compressionData['count'] === null,
            'message' => $compressionData['message']
        ]);
    }

    public function compress(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:png,jpg,jpeg|max:5120',
        ]);

        try {
            \Tinify\Tinify::setKey(config('services.tinypng.key'));

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

            if (file_exists($originalPath)) {
                unlink($originalPath);
            }

            // Get updated compression count after successful compression
            $newCount = \Tinify\Tinify::getCompressionCount();

            return response()->json([
                'success' => true,
                'compressed_size' => $compressedSize,
                'original_size' => $originalSize,
                'savings_percent' => $savingsPercent,
                'compression_count' => $newCount, // This should now have a value (1, 2, 3, etc.)
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
}
