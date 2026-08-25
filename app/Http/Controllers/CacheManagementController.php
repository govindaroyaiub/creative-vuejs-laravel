<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Inertia\Inertia;

class CacheManagementController extends Controller
{
    public function index()
    {
        $stats = $this->getCacheStats();
        $recentCleanups = $this->getRecentCleanups();
        $systemInfo = $this->getSystemInfo();

        return Inertia::render('CacheManagement/DashboardModern', [
            'stats' => $stats,
            'recentCleanups' => $recentCleanups,
            'systemInfo' => $systemInfo,
            'lastCleanup' => $this->getLastCleanupTime(),
            // File explorer (4th tab): whitelisted roots + delete gate.
            'explorerRoots' => collect($this->explorerRoots())
                ->map(fn ($r, $id) => ['id' => $id, 'label' => $r['label']])
                ->values(),
            'canDelete' => Auth::user()?->role === 'super_admin',
        ]);
    }

    // ─── File explorer ──────────────────────────────────────────────────────────

    /** Whitelisted browse roots. Only these two trees are ever reachable. */
    private function explorerRoots(): array
    {
        return [
            'uploads' => ['label' => 'public/uploads', 'base' => public_path('uploads')],
            'storage' => ['label' => 'storage/app', 'base' => storage_path('app')],
        ];
    }

    /**
     * Resolve (root id + relative path) to a safe absolute path that is proven to
     * sit inside the whitelisted root. Returns null on any escape / bad input —
     * this is the single guard against path traversal.
     */
    private function explorerResolve(string $rootId, string $rel): ?array
    {
        $roots = $this->explorerRoots();
        if (! isset($roots[$rootId])) return null;

        $base = $roots[$rootId]['base'];
        if (! is_dir($base)) @mkdir($base, 0775, true);
        $baseReal = realpath($base);
        if ($baseReal === false) return null;

        $rel = ltrim(str_replace('\\', '/', $rel), '/');
        $real = realpath($rel === '' ? $baseReal : $baseReal . DIRECTORY_SEPARATOR . $rel);
        if ($real === false) return null;

        // Must be the root itself or strictly inside it.
        if ($real !== $baseReal && ! str_starts_with($real, $baseReal . DIRECTORY_SEPARATOR)) return null;

        return ['base' => $baseReal, 'real' => $real];
    }

    /** List one directory (folders first, then files). */
    public function explorerBrowse(Request $request)
    {
        $data = $request->validate(['root' => 'required|string', 'path' => 'nullable|string']);
        $resolved = $this->explorerResolve($data['root'], $data['path'] ?? '');
        if (! $resolved || ! is_dir($resolved['real'])) {
            return response()->json(['error' => 'Invalid path'], 404);
        }

        [$base, $dir] = [$resolved['base'], $resolved['real']];
        $entries = [];
        foreach (scandir($dir) as $name) {
            if ($name === '.' || $name === '..') continue;
            $full = $dir . DIRECTORY_SEPARATOR . $name;
            $isDir = is_dir($full);
            $rel = ltrim(str_replace('\\', '/', substr($full, strlen($base))), '/');
            $entries[] = [
                'name' => $name,
                'type' => $isDir ? 'dir' : 'file',
                'size' => $isDir ? null : @filesize($full),
                'modified' => date('Y-m-d H:i', @filemtime($full) ?: time()),
                'path' => $rel,
                'ext' => $isDir ? null : strtolower(pathinfo($name, PATHINFO_EXTENSION)),
            ];
        }
        usort($entries, fn ($a, $b) => [$a['type'] === 'file', strtolower($a['name'])] <=> [$b['type'] === 'file', strtolower($b['name'])]);

        $relDir = ltrim(str_replace('\\', '/', substr($dir, strlen($base))), '/');

        return response()->json(['root' => $data['root'], 'path' => $relDir, 'entries' => $entries]);
    }

    /** Recursively search a whole root tree by filename (case-insensitive). */
    public function explorerSearch(Request $request)
    {
        $data = $request->validate(['root' => 'required|string', 'q' => 'required|string|min:1|max:255']);
        $roots = $this->explorerRoots();
        if (! isset($roots[$data['root']])) return response()->json(['error' => 'Invalid root'], 404);

        $base = realpath($roots[$data['root']]['base']);
        if ($base === false) {
            return response()->json(['root' => $data['root'], 'query' => $data['q'], 'entries' => [], 'capped' => false]);
        }

        $needle = mb_strtolower(trim($data['q']));
        $max = 500;
        $entries = [];
        try {
            $it = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($base, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::SELF_FIRST,
                \RecursiveIteratorIterator::CATCH_GET_CHILD, // skip unreadable dirs instead of aborting
            );
            foreach ($it as $info) {
                if (! str_contains(mb_strtolower($info->getFilename()), $needle)) continue;
                $full = $info->getPathname();
                $isDir = $info->isDir();
                $rel = ltrim(str_replace('\\', '/', substr($full, strlen($base))), '/');
                $parent = trim(str_replace('\\', '/', dirname($rel)), '.');
                $entries[] = [
                    'name' => $info->getFilename(),
                    'type' => $isDir ? 'dir' : 'file',
                    'size' => $isDir ? null : $info->getSize(),
                    'modified' => date('Y-m-d H:i', $info->getMTime()),
                    'path' => $rel,
                    'parent' => $parent === '' ? $data['root'] : $data['root'] . '/' . $parent,
                    'ext' => $isDir ? null : strtolower($info->getExtension()),
                ];
                if (count($entries) >= $max) break;
            }
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Search failed'], 500);
        }

        usort($entries, fn ($a, $b) => [$a['type'] === 'file', strtolower($a['name'])] <=> [$b['type'] === 'file', strtolower($b['name'])]);

        return response()->json([
            'root' => $data['root'], 'query' => $data['q'],
            'entries' => $entries, 'capped' => count($entries) >= $max,
        ]);
    }

    /** Stream one file as a download. */
    public function explorerDownload(Request $request)
    {
        $data = $request->validate(['root' => 'required|string', 'path' => 'required|string']);
        $resolved = $this->explorerResolve($data['root'], $data['path']);
        if (! $resolved || ! is_file($resolved['real'])) abort(404);

        return response()->download($resolved['real']);
    }

    /** Delete a file or folder. Super-admins only (destructive). */
    public function explorerDestroy(Request $request)
    {
        if (Auth::user()?->role !== 'super_admin') {
            return response()->json(['error' => 'Only super admins can delete.'], 403);
        }
        $data = $request->validate(['root' => 'required|string', 'path' => 'required|string']);
        $resolved = $this->explorerResolve($data['root'], $data['path']);
        if (! $resolved) return response()->json(['error' => 'Invalid path'], 404);

        // Never delete a whitelisted root itself.
        if ($resolved['real'] === $resolved['base']) {
            return response()->json(['error' => 'Cannot delete the root folder.'], 422);
        }

        is_dir($resolved['real'])
            ? File::deleteDirectory($resolved['real'])
            : File::delete($resolved['real']);

        return response()->json(['success' => true]);
    }

    public function runCleanup(Request $request)
    {
        // Add extensive logging for debugging
        Log::info('Cache cleanup request received', [
            'type' => $request->input('type'),
            'user_id' => Auth::id(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent()
        ]);

        $request->validate([
            'type' => 'required|string|in:all,laravel,storage,logs,temp'
        ]);

        try {
            $type = $request->input('type', 'all');

            Log::info('Starting cache cleanup', ['type' => $type]);

            // Directly implement cleanup logic instead of calling missing artisan command
            $result = $this->performCleanup($type);

            // Log the cleanup activity to cache_cleanup.log for the Activity tab
            $this->logCleanup($result);

            Log::info('Cache cleanup completed', ['result' => $result]);

            return response()->json([
                'success' => true,
                'message' => 'Cache cleanup completed successfully',
                'summary' => $result,
                'timestamp' => now()->toISOString()
            ]);
        } catch (\Exception $e) {
            Log::error('Cache cleanup failed', [
                'error' => $e->getMessage(),
                'type' => $request->input('type', 'all'),
                'user_id' => Auth::id(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Cache cleanup failed: ' . $e->getMessage(),
                'debug' => [
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]
            ], 500);
        }
    }

    private function performCleanup($type)
    {
        $totalFiles = 0;
        $totalSize = 0;
        $results = [];

        switch ($type) {
            case 'all':
                $results[] = $this->cleanLaravelCache();
                $results[] = $this->cleanStorageCache();
                $results[] = $this->cleanLogsCache();
                $results[] = $this->cleanTempFiles();
                break;
            case 'laravel':
                $results[] = $this->cleanLaravelCache();
                break;
            case 'storage':
                $results[] = $this->cleanStorageCache();
                break;
            case 'logs':
                $results[] = $this->cleanLogsCache();
                break;
            case 'temp':
                $results[] = $this->cleanTempFiles();
                break;
        }

        // Sum up results
        foreach ($results as $result) {
            $totalFiles += $result['files'];
            $totalSize += $result['size'];
        }

        return [
            'total_files' => $totalFiles,
            'total_size' => $totalSize,
            'total_size_formatted' => $this->formatBytes($totalSize),
            'details' => $results
        ];
    }

    private function cleanLaravelCache()
    {
        $files = 0;
        $size = 0;

        try {
            // Clear Laravel caches
            Artisan::call('cache:clear');
            Artisan::call('config:clear');
            Artisan::call('route:clear');
            Artisan::call('view:clear');

            // Count framework cache files that were cleared
            $cachePaths = [
                storage_path('framework/cache/data'),
                storage_path('framework/views'),
                base_path('bootstrap/cache')
            ];

            foreach ($cachePaths as $path) {
                if (is_dir($path)) {
                    $iterator = new \RecursiveIteratorIterator(
                        new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS)
                    );
                    foreach ($iterator as $file) {
                        if ($file->isFile()) {
                            $files++;
                            $size += $file->getSize();
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            Log::warning('Laravel cache cleanup had issues: ' . $e->getMessage());
        }

        return [
            'type' => 'Laravel Cache',
            'files' => $files,
            'size' => $size,
            'formatted_size' => $this->formatBytes($size)
        ];
    }

    private function cleanStorageCache()
    {
        $files = 0;
        $size = 0;

        try {
            $storagePaths = [
                storage_path('app/cache'),
                storage_path('app/temp'),
                public_path('uploads/temp')
            ];

            foreach ($storagePaths as $path) {
                if (is_dir($path)) {
                    $result = $this->cleanDirectory($path);
                    $files += $result['files'];
                    $size += $result['size'];
                }
            }
        } catch (\Exception $e) {
            Log::warning('Storage cache cleanup had issues: ' . $e->getMessage());
        }

        return [
            'type' => 'Storage Cache',
            'files' => $files,
            'size' => $size,
            'formatted_size' => $this->formatBytes($size)
        ];
    }

    private function cleanLogsCache()
    {
        $files = 0;
        $size = 0;

        try {
            $logPath = storage_path('logs');
            if (is_dir($logPath)) {
                // Only remove old log files, not recent ones
                $cutoffDate = now()->subDays(7);
                $iterator = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($logPath, \RecursiveDirectoryIterator::SKIP_DOTS)
                );

                foreach ($iterator as $file) {
                    if ($file->isFile() && $file->getMTime() < $cutoffDate->timestamp) {
                        $size += $file->getSize();
                        $files++;
                        unlink($file->getRealPath());
                    }
                }
            }
        } catch (\Exception $e) {
            Log::warning('Logs cache cleanup had issues: ' . $e->getMessage());
        }

        return [
            'type' => 'Old Log Files',
            'files' => $files,
            'size' => $size,
            'formatted_size' => $this->formatBytes($size)
        ];
    }

    private function cleanTempFiles()
    {
        $files = 0;
        $size = 0;

        try {
            $tempPaths = [
                storage_path('app/tmp'),
                storage_path('app/uploads/temp'),
                public_path('temp'),
                sys_get_temp_dir() . '/laravel-*'
            ];

            foreach ($tempPaths as $path) {
                if (strpos($path, '*') !== false) {
                    // Handle glob patterns
                    $matchedPaths = glob($path);
                    foreach ($matchedPaths as $matchedPath) {
                        if (is_dir($matchedPath)) {
                            $result = $this->cleanDirectory($matchedPath);
                            $files += $result['files'];
                            $size += $result['size'];
                        }
                    }
                } elseif (is_dir($path)) {
                    $result = $this->cleanDirectory($path);
                    $files += $result['files'];
                    $size += $result['size'];
                }
            }
        } catch (\Exception $e) {
            Log::warning('Temp files cleanup had issues: ' . $e->getMessage());
        }

        return [
            'type' => 'Temporary Files',
            'files' => $files,
            'size' => $size,
            'formatted_size' => $this->formatBytes($size)
        ];
    }

    private function cleanDirectory($path)
    {
        $files = 0;
        $size = 0;

        if (!is_dir($path)) {
            return ['files' => 0, 'size' => 0];
        }

        try {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST
            );

            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $size += $file->getSize();
                    $files++;
                    unlink($file->getRealPath());
                } elseif ($file->isDir()) {
                    rmdir($file->getRealPath());
                }
            }
        } catch (\Exception $e) {
            Log::warning("Failed to clean directory {$path}: " . $e->getMessage());
        }

        return ['files' => $files, 'size' => $size];
    }

    private function getLastCleanupSummary()
    {
        $logFile = storage_path('logs/cache_cleanup.log');

        if (File::exists($logFile) && File::size($logFile) > 0) {
            try {
                $lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                if (!empty($lines)) {
                    $lastLine = end($lines);
                    $data = json_decode($lastLine, true);

                    if ($data && isset($data['total_files'], $data['total_size'])) {
                        return [
                            'total_files' => $data['total_files'],
                            'total_size' => $data['total_size'],
                            'total_size_formatted' => $this->formatBytes($data['total_size']),
                            'timestamp' => $data['timestamp'] ?? now()->toISOString()
                        ];
                    }
                }
            } catch (\Exception $e) {
                Log::warning('Error reading cleanup summary: ' . $e->getMessage());
            }
        }

        return [
            'total_files' => 0,
            'total_size' => 0,
            'total_size_formatted' => '0 B',
            'timestamp' => now()->toISOString()
        ];
    }

    public function getStats()
    {
        return response()->json($this->getCacheStats());
    }

    public function getServerTime()
    {
        // Default to Bangladesh timezone for this user
        $userTimezone = session('user_timezone', 'Asia/Dhaka');

        $userTime = now()->setTimezone($userTimezone);
        $utcTime = now('UTC');

        return response()->json([
            'server_time' => $userTime->format('Y-m-d H:i:s'),
            'timestamp' => $userTime->timestamp,
            'timezone' => $userTimezone,
            'iso' => $userTime->toISOString(),
            'utc_time' => $utcTime->format('Y-m-d H:i:s T')
        ]);
    }

    private function getCacheStats()
    {
        $stats = [];

        // Laravel Cache Stats
        $stats['laravel_cache'] = [
            'name' => 'Laravel Cache',
            'size' => $this->getFolderSize(storage_path('framework/cache')),
            'files' => $this->getFileCount(storage_path('framework/cache')),
            'icon' => '🗂️',
            'color' => 'blue'
        ];

        // Storage Temp Stats
        $stats['storage_temp'] = [
            'name' => 'Storage Temp',
            'size' => $this->getFolderSize(storage_path('app/temp')),
            'files' => $this->getFileCount(storage_path('app/temp')),
            'icon' => '📁',
            'color' => 'green'
        ];

        // Note: Log Files stats moved to dedicated systemInfo section

        // Compiled Views Stats
        $stats['compiled_views'] = [
            'name' => 'Compiled Views',
            'size' => $this->getFolderSize(storage_path('framework/views')),
            'files' => $this->getFileCount(storage_path('framework/views')),
            'icon' => '👁️',
            'color' => 'purple'
        ];

        // Temp Uploads Stats
        $stats['temp_uploads'] = [
            'name' => 'Temp Uploads',
            'size' => $this->getFolderSize(public_path('uploads/temp')),
            'files' => $this->getFileCount(public_path('uploads/temp')),
            'icon' => '🗃️',
            'color' => 'red'
        ];

        return $stats;
    }

    private function getRecentCleanups()
    {
        $logFile = storage_path('logs/cache_cleanup.log');
        $cleanups = [];

        if (File::exists($logFile) && File::size($logFile) > 0) {
            try {
                // Read all lines and get the last 10
                $allLines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                $lastLines = array_slice($allLines, -10); // Get last 10 lines

                foreach ($lastLines as $line) {
                    $line = trim($line);
                    if (!empty($line)) {
                        $data = json_decode($line, true);
                        if ($data && isset($data['timestamp'])) {
                            $cleanups[] = [
                                'timestamp' => Carbon::parse($data['timestamp'])->format('Y-m-d H:i:s'),
                                'total_files' => $data['total_files'] ?? 0,
                                'total_size' => $this->formatBytes($data['total_size'] ?? 0),
                                'human_time' => Carbon::parse($data['timestamp'])->diffForHumans()
                            ];
                        }
                    }
                }
            } catch (\Exception $e) {
                // If there's any error reading the file, return empty array
                Log::warning('Error reading cache cleanup log: ' . $e->getMessage());
            }
        }

        return array_reverse($cleanups); // Most recent first
    }

    /**
     * API endpoint to get recent cleanups for the Activity tab
     */
    public function getRecentCleanupsApi()
    {
        try {
            $cleanups = $this->getRecentCleanups();

            return response()->json([
                'success' => true,
                'data' => $cleanups,
                'count' => count($cleanups)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch recent cleanups: ' . $e->getMessage(),
                'data' => []
            ], 500);
        }
    }

    private function getSystemInfo()
    {
        $userTimezone = session('user_timezone', 'Asia/Dhaka');

        // Get Laravel log files
        $laravelLogSize = 0;
        $laravelLogCount = 0;
        $laravelLogFiles = [];
        $laravelLogPattern = storage_path('logs/laravel*.log');
        $laravelLogs = glob($laravelLogPattern);

        foreach ($laravelLogs as $logFile) {
            if (file_exists($logFile)) {
                $size = filesize($logFile);
                $laravelLogSize += $size;
                $laravelLogCount++;
                $laravelLogFiles[] = [
                    'name' => basename($logFile),
                    'size' => $size,
                    'formatted_size' => $this->formatBytes($size),
                    'modified' => date('Y-m-d H:i:s', filemtime($logFile))
                ];
            }
        }

        // Get cache management logs
        $cacheLogSize = 0;
        $cacheLogCount = 0;
        $cacheLogFiles = [];

        $cacheLogPaths = [
            'cache_cleanup.log',
            'cache_cleanup_errors.log'
        ];

        foreach ($cacheLogPaths as $logFileName) {
            $logFile = storage_path('logs/' . $logFileName);
            if (file_exists($logFile)) {
                $size = filesize($logFile);
                $cacheLogSize += $size;
                $cacheLogCount++;
                $cacheLogFiles[] = [
                    'name' => $logFileName,
                    'size' => $size,
                    'formatted_size' => $this->formatBytes($size),
                    'modified' => date('Y-m-d H:i:s', filemtime($logFile))
                ];
            }
        }

        $totalLogSize = $laravelLogSize + $cacheLogSize;
        $needsAttention = $totalLogSize > (20 * 1024 * 1024); // 20MB threshold

        return [
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'storage_path' => storage_path(),
            'disk_usage' => [
                'total' => $this->formatBytes(disk_total_space(storage_path())),
                'free' => $this->formatBytes(disk_free_space(storage_path())),
                'used_percentage' => round((1 - disk_free_space(storage_path()) / disk_total_space(storage_path())) * 100, 2)
            ],
            'server_time' => now()->setTimezone($userTimezone)->format('Y-m-d H:i:s T'),
            'timezone' => $userTimezone,
            'detected_timezone' => session('user_timezone'),
            'is_timezone_detected' => session()->has('user_timezone'),
            'logs' => [
                'laravel' => [
                    'size' => $laravelLogSize,
                    'formatted_size' => $this->formatBytes($laravelLogSize),
                    'count' => $laravelLogCount,
                    'files' => $laravelLogFiles
                ],
                'cache_management' => [
                    'size' => $cacheLogSize,
                    'formatted_size' => $this->formatBytes($cacheLogSize),
                    'count' => $cacheLogCount,
                    'files' => $cacheLogFiles
                ],
                'total' => [
                    'size' => $totalLogSize,
                    'formatted_size' => $this->formatBytes($totalLogSize),
                    'count' => $laravelLogCount + $cacheLogCount,
                    'needs_attention' => $needsAttention
                ]
            ]
        ];
    }

    private function getLastCleanupTime()
    {
        $logFile = storage_path('logs/cache_cleanup.log');

        if (File::exists($logFile) && File::size($logFile) > 0) {
            try {
                // Read the last line safely
                $lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                if (!empty($lines)) {
                    $lastLine = end($lines);
                    $data = json_decode($lastLine, true);

                    if ($data && isset($data['timestamp'])) {
                        return [
                            'timestamp' => $data['timestamp'],
                            'human' => Carbon::parse($data['timestamp'])->diffForHumans(),
                            'formatted' => Carbon::parse($data['timestamp'])->format('Y-m-d H:i:s')
                        ];
                    }
                }
            } catch (\Exception $e) {
                Log::warning('Error reading last cleanup time: ' . $e->getMessage());
            }
        }

        return null;
    }

    private function getCurrentScheduleSettings()
    {
        if (Storage::exists('cache_schedule.json')) {
            $settings = json_decode(Storage::get('cache_schedule.json'), true);
            return $settings;
        }

        // Default settings
        return [
            'enabled' => true,
            'time' => '04:30',
            'type' => 'all',
            'frequency' => 'daily'
        ];
    }

    private function getFolderSize($path)
    {
        $size = 0;

        if (File::exists($path) && is_dir($path)) {
            try {
                $files = File::allFiles($path);
                foreach ($files as $file) {
                    $size += $file->getSize();
                }
            } catch (\Exception $e) {
                // Handle permission issues
            }
        }

        return $size;
    }

    private function getFileCount($path)
    {
        if (File::exists($path) && is_dir($path)) {
            try {
                return count(File::allFiles($path));
            } catch (\Exception $e) {
                return 0;
            }
        }

        return 0;
    }

    private function formatBytes($size)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $size > 1024; $i++) {
            $size /= 1024;
        }

        return round($size, 2) . ' ' . $units[$i];
    }

    public function blankLogFiles()
    {
        try {
            $clearedFiles = [];
            $totalSizeBefore = 0;

            // Get main Laravel log files
            $laravelLogPattern = storage_path('logs/laravel*.log');
            $laravelLogs = glob($laravelLogPattern);

            foreach ($laravelLogs as $logFile) {
                if (file_exists($logFile)) {
                    $totalSizeBefore += filesize($logFile);
                    file_put_contents($logFile, ''); // Blank the file
                    $clearedFiles[] = basename($logFile);
                }
            }

            // Get cache cleanup log
            $cacheLogFile = storage_path('logs/cache_cleanup.log');
            if (file_exists($cacheLogFile)) {
                $totalSizeBefore += filesize($cacheLogFile);
                file_put_contents($cacheLogFile, ''); // Blank the file
                $clearedFiles[] = 'cache_cleanup.log';
            }

            // Get cache cleanup errors log
            $cacheErrorLogFile = storage_path('logs/cache_cleanup_errors.log');
            if (file_exists($cacheErrorLogFile)) {
                $totalSizeBefore += filesize($cacheErrorLogFile);
                file_put_contents($cacheErrorLogFile, ''); // Blank the file
                $clearedFiles[] = 'cache_cleanup_errors.log';
            }

            return response()->json([
                'success' => true,
                'message' => 'Log files have been blanked successfully',
                'files_cleared' => $clearedFiles,
                'count' => count($clearedFiles),
                'size_freed' => $this->formatBytes($totalSizeBefore),
                'timestamp' => now()->toISOString()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to blank log files: ' . $e->getMessage()
            ], 422);
        }
    }

    public function getSystemInfoOnly()
    {
        return response()->json($this->getSystemInfo());
    }

    public function runArtisanClear(Request $request)
    {
        try {
            $commands = [
                'cache:clear',
                'config:clear',
                'route:clear',
                'view:clear',
                'config:cache'
            ];

            $results = [];
            $allSuccessful = true;

            foreach ($commands as $command) {
                try {
                    $exitCode = Artisan::call($command);
                    $output = Artisan::output();

                    $results[$command] = [
                        'success' => $exitCode === 0,
                        'output' => trim($output),
                        'exit_code' => $exitCode
                    ];

                    if ($exitCode !== 0) {
                        $allSuccessful = false;
                    }
                } catch (\Exception $e) {
                    $results[$command] = [
                        'success' => false,
                        'output' => $e->getMessage(),
                        'exit_code' => 1
                    ];
                    $allSuccessful = false;
                }
            }

            return response()->json([
                'success' => $allSuccessful,
                'message' => $allSuccessful ? 'All artisan commands executed successfully' : 'Some commands failed',
                'commands_run' => count($commands),
                'results' => $results,
                'timestamp' => now()->toISOString()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to run artisan commands: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Log cleanup activity to cache_cleanup.log for the Activity tab
     */
    private function logCleanup($result)
    {
        try {
            $logFile = storage_path('logs/cache_cleanup.log');

            // Create the log entry
            $logEntry = [
                'timestamp' => now()->toISOString(),
                'total_files' => $result['total_files'] ?? 0,
                'total_size' => $result['total_size'] ?? 0,
                'total_size_formatted' => $result['total_size_formatted'] ?? '0 B',
                'details' => $result['details'] ?? [],
                'user_id' => Auth::id(),
                'ip' => request()->ip()
            ];

            // Append to log file
            File::append($logFile, json_encode($logEntry) . "\n");
        } catch (\Exception $e) {
            // If logging fails, just log to Laravel log instead
            Log::warning('Failed to log cleanup activity: ' . $e->getMessage());
        }
    }
}
