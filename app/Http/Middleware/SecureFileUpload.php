<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;
use App\Services\VirusScanService;

class SecureFileUpload
{
    protected VirusScanService $virusScanService;

    public function __construct(VirusScanService $virusScanService)
    {
        $this->virusScanService = $virusScanService;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only process if there are file uploads
        if ($request->hasFile('file')) {
            $files = is_array($request->file('file')) ? $request->file('file') : [$request->file('file')];

            foreach ($files as $file) {
                // Standard file validation
                if (!$this->validateFileUpload($file)) {
                    return response()->json([
                        'message' => 'Invalid file upload detected.',
                        'error' => 'File validation failed for security reasons.'
                    ], 422);
                }

                // Virus scanning
                $scanResult = $this->virusScanService->scanFile($file);
                if (!$scanResult['clean']) {
                    Log::critical('VIRUS DETECTED in upload', [
                        'filename' => $file->getClientOriginalName(),
                        'ip' => $request->ip(),
                        'threat' => $scanResult['threat'] ?? 'Unknown',
                        'user_agent' => $request->userAgent(),
                    ]);

                    return response()->json([
                        'message' => 'File rejected - security threat detected.',
                        'error' => 'Malicious content found in uploaded file.'
                    ], 422);
                }
            }
        }

        return $next($request);
    }

    /**
     * Comprehensive file validation
     */
    private function validateFileUpload($file): bool
    {
        // Basic file checks
        if (!$file || !$file->isValid()) {
            Log::warning('File upload failed: Invalid file or upload error');
            return false;
        }

        // File size check (max 10MB)
        if ($file->getSize() > 10485760) {
            Log::warning('File upload failed: File too large', ['size' => $file->getSize()]);
            return false;
        }

        // Get file info
        $originalName = $file->getClientOriginalName();
        $mimeType = $file->getMimeType();
        $extension = strtolower($file->getClientOriginalExtension());

        // Allowed file types and their corresponding MIME types
        $allowedTypes = [
            'zip' => ['application/zip', 'application/x-zip-compressed'],
            'pdf' => ['application/pdf'],
            'jpg' => ['image/jpeg'],
            'jpeg' => ['image/jpeg'],
            'png' => ['image/png'],
            'gif' => ['image/gif'],
            'webp' => ['image/webp'],
            'mp4' => ['video/mp4'],
            'avi' => ['video/x-msvideo'],
            'mov' => ['video/quicktime'],
            'webm' => ['video/webm'],
            'txt' => ['text/plain'],
            'doc' => ['application/msword'],
            'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
            'xls' => ['application/vnd.ms-excel'],
            'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        ];

        // Check if extension is allowed
        if (!array_key_exists($extension, $allowedTypes)) {
            Log::warning('File upload failed: Disallowed extension', ['extension' => $extension]);
            return false;
        }

        // Check if MIME type matches extension
        if (!in_array($mimeType, $allowedTypes[$extension])) {
            Log::warning('File upload failed: MIME type mismatch', [
                'extension' => $extension,
                'mime_type' => $mimeType,
                'expected' => $allowedTypes[$extension]
            ]);
            return false;
        }

        // File signature validation (magic bytes)
        if (!$this->validateFileSignature($file, $extension)) {
            Log::warning('File upload failed: Invalid file signature', ['extension' => $extension]);
            return false;
        }

        // Check for dangerous filenames
        if ($this->containsDangerousPatterns($originalName)) {
            Log::warning('File upload failed: Dangerous filename pattern', ['filename' => $originalName]);
            return false;
        }

        // Check for embedded executable content in images
        if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
            if (!$this->validateImageFile($file)) {
                Log::warning('File upload failed: Suspicious image content');
                return false;
            }
        }

        return true;
    }

    /**
     * Validate file signature (magic bytes)
     */
    private function validateFileSignature($file, string $extension): bool
    {
        $fileSignatures = [
            'zip' => ['504B0304', '504B0506', '504B0708'],
            'pdf' => ['25504446'],
            'jpg' => ['FFD8FF'],
            'jpeg' => ['FFD8FF'],
            'png' => ['89504E47'],
            'gif' => ['47494638'],
            'webp' => ['52494646'],
            'mp4' => ['66747970'],
            'avi' => ['52494646'],
            'mov' => ['66747970'],
            'webm' => ['1A45DFA3']
        ];

        if (!isset($fileSignatures[$extension])) {
            return true; // Skip signature check for unsupported types
        }

        $handle = fopen($file->getRealPath(), 'rb');
        if (!$handle) {
            return false;
        }

        $bytes = fread($handle, 8);
        fclose($handle);

        $hex = strtoupper(bin2hex($bytes));

        foreach ($fileSignatures[$extension] as $signature) {
            if (strpos($hex, $signature) === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check for dangerous filename patterns
     */
    private function containsDangerousPatterns(string $filename): bool
    {
        $dangerousPatterns = [
            '/\.(php|phtml|php3|php4|php5|phar|exe|bat|cmd|com|scr|vbs|js|jar|asp|aspx|jsp)$/i',
            '/\.\w+\.php$/i', // Double extensions like .jpg.php
            '/[<>:"\/\\|?*\x00-\x1f]/', // Control characters and dangerous symbols
            '/^(con|prn|aux|nul|com[1-9]|lpt[1-9])$/i', // Windows reserved names
            '/\.(htaccess|htpasswd|web\.config)$/i', // Server configuration files
        ];

        foreach ($dangerousPatterns as $pattern) {
            if (preg_match($pattern, $filename)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Validate image files for embedded executables
     */
    private function validateImageFile($file): bool
    {
        try {
            // Read first 1KB of the file to check for suspicious content
            $handle = fopen($file->getRealPath(), 'rb');
            if (!$handle) {
                return false;
            }

            $content = fread($handle, 1024);
            fclose($handle);

            // Check for common executable signatures within image
            $suspiciousPatterns = [
                '/\x4D\x5A/', // MZ header (PE executable)
                '/\x7F\x45\x4C\x46/', // ELF header
                '/\x89\x50\x4E\x47.*<\?php/s', // PNG with embedded PHP
                '/\xFF\xD8\xFF.*<\?php/s', // JPEG with embedded PHP
                '/<\?php/', // Direct PHP tags
                '/<script/', // JavaScript tags
                '/eval\s*\(/', // eval() calls
                '/base64_decode\s*\(/', // base64_decode calls
            ];

            foreach ($suspiciousPatterns as $pattern) {
                if (preg_match($pattern, $content)) {
                    return false;
                }
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Image validation error: ' . $e->getMessage());
            return false;
        }
    }
}
