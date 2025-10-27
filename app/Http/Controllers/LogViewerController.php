<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Response;
use Inertia\Inertia;
use Carbon\Carbon;

class LogViewerController extends Controller
{
    private $allowedLogFiles = [
        'laravel.log',
        'cache_cleanup.log',
        'cache_cleanup_errors.log'
    ];

    public function index()
    {
        $logFiles = $this->getAvailableLogFiles();
        $selectedFile = request('file', 'laravel.log');

        if (!in_array($selectedFile, $this->allowedLogFiles)) {
            $selectedFile = 'laravel.log';
        }

        $logData = $this->getLogContent($selectedFile);

        return Inertia::render('LogViewer/Index', [
            'logFiles' => $logFiles,
            'selectedFile' => $selectedFile,
            'logData' => $logData,
            'refreshInterval' => 30 // seconds
        ]);
    }

    public function getLogData(Request $request)
    {
        $request->validate([
            'file' => 'required|string',
            'lines' => 'nullable|integer|min:10|max:1000',
            'search' => 'nullable|string|max:200',
            'level' => 'nullable|string|in:emergency,alert,critical,error,warning,notice,info,debug,all'
        ]);

        $fileName = $request->input('file');
        $lines = $request->input('lines', 100);
        $search = $request->input('search');
        $level = $request->input('level', 'all');

        if (!in_array($fileName, $this->allowedLogFiles)) {
            return response()->json(['error' => 'Invalid log file'], 400);
        }

        $logData = $this->getLogContent($fileName, $lines, $search, $level);

        return response()->json($logData);
    }

    public function downloadLog(Request $request)
    {
        $fileName = $request->input('file');

        if (!in_array($fileName, $this->allowedLogFiles)) {
            abort(404);
        }

        $filePath = storage_path('logs/' . $fileName);

        if (!File::exists($filePath)) {
            abort(404, 'Log file not found');
        }

        return Response::download($filePath, $fileName);
    }

    public function clearLog(Request $request)
    {
        $request->validate([
            'file' => 'required|string'
        ]);

        $fileName = $request->input('file');

        if (!in_array($fileName, $this->allowedLogFiles)) {
            return response()->json(['error' => 'Invalid log file'], 400);
        }

        $filePath = storage_path('logs/' . $fileName);

        if (!File::exists($filePath)) {
            return response()->json(['error' => 'Log file not found'], 404);
        }

        try {
            file_put_contents($filePath, '');

            return response()->json([
                'success' => true,
                'message' => "Log file '{$fileName}' cleared successfully",
                'timestamp' => now()->toISOString()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to clear log file: ' . $e->getMessage()
            ], 500);
        }
    }

    private function getAvailableLogFiles()
    {
        $files = [];

        foreach ($this->allowedLogFiles as $fileName) {
            $filePath = storage_path('logs/' . $fileName);

            if (File::exists($filePath)) {
                $files[] = [
                    'name' => $fileName,
                    'display_name' => $this->getDisplayName($fileName),
                    'size' => File::size($filePath),
                    'formatted_size' => $this->formatBytes(File::size($filePath)),
                    'modified' => Carbon::createFromTimestamp(File::lastModified($filePath))->format('Y-m-d H:i:s'),
                    'icon' => $this->getFileIcon($fileName),
                    'type' => $this->getFileType($fileName)
                ];
            }
        }

        // Also check for dated Laravel logs (laravel-YYYY-MM-DD.log)
        $laravelLogPattern = storage_path('logs/laravel-*.log');
        $datedLogs = glob($laravelLogPattern);

        foreach ($datedLogs as $logPath) {
            $fileName = basename($logPath);
            if (!in_array($fileName, $this->allowedLogFiles)) {
                $this->allowedLogFiles[] = $fileName; // Dynamically allow dated logs

                $files[] = [
                    'name' => $fileName,
                    'display_name' => $this->getDisplayName($fileName),
                    'size' => File::size($logPath),
                    'formatted_size' => $this->formatBytes(File::size($logPath)),
                    'modified' => Carbon::createFromTimestamp(File::lastModified($logPath))->format('Y-m-d H:i:s'),
                    'icon' => '📅',
                    'type' => 'laravel_dated'
                ];
            }
        }

        // Sort by modification time (newest first)
        usort($files, function ($a, $b) {
            return strtotime($b['modified']) - strtotime($a['modified']);
        });

        return $files;
    }

    private function getLogContent($fileName, $lines = 100, $search = null, $level = 'all')
    {
        $filePath = storage_path('logs/' . $fileName);

        if (!File::exists($filePath)) {
            return [
                'content' => [],
                'file_info' => null,
                'total_lines' => 0,
                'filtered_lines' => 0,
                'error' => 'Log file not found'
            ];
        }

        $fileInfo = [
            'name' => $fileName,
            'size' => File::size($filePath),
            'formatted_size' => $this->formatBytes(File::size($filePath)),
            'modified' => Carbon::createFromTimestamp(File::lastModified($filePath))->format('Y-m-d H:i:s'),
            'readable' => is_readable($filePath)
        ];

        try {
            $content = [];
            $totalLines = 0;
            $filteredLines = 0;

            if ($fileInfo['size'] > 0) {
                // For large files, read from the end
                $handle = fopen($filePath, 'r');

                if ($handle) {
                    // Get all lines first
                    $allLines = [];
                    while (($line = fgets($handle)) !== false) {
                        $allLines[] = trim($line);
                        $totalLines++;
                    }
                    fclose($handle);

                    // Get the last N lines
                    $relevantLines = array_slice($allLines, -$lines);

                    foreach ($relevantLines as $index => $line) {
                        if (empty($line)) continue;

                        $parsed = $this->parseLogLine($line);

                        // Apply filters
                        if ($search && stripos($line, $search) === false) {
                            continue;
                        }

                        if ($level !== 'all' && $parsed['level'] !== $level) {
                            continue;
                        }

                        $content[] = [
                            'line_number' => $totalLines - count($relevantLines) + $index + 1,
                            'raw' => $line,
                            'parsed' => $parsed,
                            'timestamp' => $parsed['timestamp'],
                            'level' => $parsed['level'],
                            'message' => $parsed['message'],
                            'context' => $parsed['context'],
                            'formatted_time' => $parsed['formatted_time']
                        ];

                        $filteredLines++;
                    }
                }
            }

            return [
                'content' => array_reverse($content), // Most recent first
                'file_info' => $fileInfo,
                'total_lines' => $totalLines,
                'filtered_lines' => $filteredLines,
                'showing_last' => $lines,
                'has_search' => !empty($search),
                'search_term' => $search,
                'level_filter' => $level
            ];
        } catch (\Exception $e) {
            return [
                'content' => [],
                'file_info' => $fileInfo,
                'total_lines' => 0,
                'filtered_lines' => 0,
                'error' => 'Error reading log file: ' . $e->getMessage()
            ];
        }
    }

    private function parseLogLine($line)
    {
        // Default parsed structure
        $parsed = [
            'timestamp' => null,
            'level' => 'info',
            'message' => $line,
            'context' => null,
            'formatted_time' => null,
            'is_json' => false
        ];

        // Try to parse as JSON first (for cache cleanup logs)
        if (strpos($line, '{') === 0) {
            $jsonData = json_decode($line, true);
            if ($jsonData && isset($jsonData['timestamp'])) {
                $parsed['is_json'] = true;
                $parsed['timestamp'] = $jsonData['timestamp'];
                $parsed['level'] = $jsonData['level'] ?? 'info';
                $parsed['message'] = $jsonData['message'] ?? 'Cache cleanup operation';
                $parsed['context'] = $jsonData;

                try {
                    $parsed['formatted_time'] = Carbon::parse($jsonData['timestamp'])->format('Y-m-d H:i:s');
                } catch (\Exception $e) {
                    $parsed['formatted_time'] = $jsonData['timestamp'];
                }

                return $parsed;
            }
        }

        // Try to parse Laravel log format
        // Pattern: [YYYY-MM-DD HH:MM:SS] environment.LEVEL: message
        if (preg_match('/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\] \w+\.(\w+): (.+)$/', $line, $matches)) {
            $parsed['timestamp'] = $matches[1];
            $parsed['level'] = strtolower($matches[2]);
            $parsed['message'] = $matches[3];
            $parsed['formatted_time'] = $matches[1];

            // Try to extract JSON context from message
            if (preg_match('/^(.+?) (\{.+\})$/', $parsed['message'], $contextMatches)) {
                $parsed['message'] = trim($contextMatches[1]);
                $contextJson = json_decode($contextMatches[2], true);
                if ($contextJson) {
                    $parsed['context'] = $contextJson;
                }
            }
        }

        return $parsed;
    }

    private function getDisplayName($fileName)
    {
        $names = [
            'laravel.log' => 'Laravel Application Log',
            'cache_cleanup.log' => 'Cache Cleanup Log',
            'cache_cleanup_errors.log' => 'Cache Cleanup Errors'
        ];

        if (isset($names[$fileName])) {
            return $names[$fileName];
        }

        // Handle dated logs
        if (preg_match('/^laravel-(\d{4}-\d{2}-\d{2})\.log$/', $fileName, $matches)) {
            return 'Laravel Log - ' . $matches[1];
        }

        return $fileName;
    }

    private function getFileIcon($fileName)
    {
        if (strpos($fileName, 'error') !== false) {
            return '🚨';
        }

        if (strpos($fileName, 'cache') !== false) {
            return '🗄️';
        }

        return '📋';
    }

    private function getFileType($fileName)
    {
        if (strpos($fileName, 'error') !== false) {
            return 'error';
        }

        if (strpos($fileName, 'cache') !== false) {
            return 'cache';
        }

        return 'application';
    }

    private function formatBytes($size)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $size > 1024; $i++) {
            $size /= 1024;
        }

        return round($size, 2) . ' ' . $units[$i];
    }
}
