<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Models\SchedulerSetting;
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
            'schedulerStatus' => $this->getSchedulerStatusData()
        ]);
    }

    public function runCleanup(Request $request)
    {
        $request->validate([
            'type' => 'required|string|in:all,laravel,storage,logs,temp'
        ]);

        try {
            $type = $request->input('type', 'all');

            // Run the cleanup command
            Artisan::call('cache:auto-cleanup', ['--type' => $type]);
            $output = Artisan::output();

            return response()->json([
                'success' => true,
                'message' => 'Cache cleanup completed successfully',
                'output' => $output,
                'timestamp' => now()->toISOString()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Cache cleanup failed: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getStats()
    {
        return response()->json($this->getCacheStats());
    }

    public function getSchedulerStatus()
    {
        return response()->json($this->getSchedulerStatusData());
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

    private function getSchedulerStatusData()
    {
        $logFile = storage_path('logs/cache_cleanup.log');
        $errorLogFile = storage_path('logs/cache_cleanup_errors.log');
        $cleanupTime = SchedulerSetting::getCacheCleanupTime();
        $isEnabled = SchedulerSetting::isCacheCleanupEnabled();

        // Use scheduler timezone consistently
        $schedulerTimezone = config('app.timezone');

        $schedulerInfo = [
            'is_configured' => $isEnabled,
            'schedule_time' => $cleanupTime,
            'timezone' => $schedulerTimezone, // Show actual scheduler timezone
            'next_run' => null,
            'last_auto_run' => null,
            'last_error' => null,
            'status' => $isEnabled ? 'active' : 'disabled',
            'total_scheduled_runs' => 0,
            'success_rate' => 100,
            'recent_errors' => []
        ];

        if ($isEnabled) {
            // Simple and reliable timezone calculation
            // Assume user is in Bangladesh timezone if not configured otherwise
            $userTimezone = session('user_timezone', 'Asia/Dhaka'); // Default to Bangladesh

            $timeParts = explode(':', $cleanupTime);
            $targetHour = (int)$timeParts[0];
            $targetMinute = (int)$timeParts[1];

            // Calculate next run in user timezone (what they expect)
            $now = now()->setTimezone($userTimezone);
            $nextRun = $now->copy()->startOfDay()->setTime($targetHour, $targetMinute);

            if ($nextRun->isPast()) {
                $nextRun->addDay();
            }

            $schedulerInfo['next_run'] = [
                'datetime' => $nextRun->format('Y-m-d H:i:s'),
                'human' => $nextRun->diffForHumans(),
                'formatted' => $nextRun->format('M j, Y \a\t g:i A T'),
                'timezone' => $userTimezone,
                'utc_time' => $nextRun->setTimezone('UTC')->format('M j, Y \a\t g:i A T')
            ];
        }

        // Check for recent errors
        if (File::exists($errorLogFile) && File::size($errorLogFile) > 0) {
            try {
                $errorLines = file($errorLogFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                $recentErrors = [];

                foreach (array_reverse($errorLines) as $line) {
                    $errorData = json_decode($line, true);
                    if ($errorData && isset($errorData['timestamp'])) {
                        $errorTime = Carbon::parse($errorData['timestamp']);

                        // Only show errors from last 7 days
                        if ($errorTime->diffInDays() <= 7) {
                            $recentErrors[] = [
                                'timestamp' => $errorData['timestamp'],
                                'human' => $errorTime->diffForHumans(),
                                'formatted' => $errorTime->format('M j, Y \a\t g:i A'),
                                'message' => $errorData['message'] ?? 'Unknown error',
                                'type' => $errorData['type'] ?? 'general'
                            ];
                        }
                    }
                }

                $schedulerInfo['recent_errors'] = array_slice($recentErrors, 0, 5);

                // If there are recent errors, set status to attention_needed
                if (!empty($recentErrors)) {
                    $lastError = $recentErrors[0];
                    $schedulerInfo['last_error'] = $lastError;

                    // If last error was recent (within 24 hours), mark as needing attention
                    $lastErrorTime = Carbon::parse($lastError['timestamp']);
                    if ($lastErrorTime->diffInHours() <= 24 && $isEnabled) {
                        $schedulerInfo['status'] = 'attention_needed';
                    }
                }
            } catch (\Exception $e) {
                Log::warning('Error reading scheduler error log: ' . $e->getMessage());
            }
        }

        // Analyze recent auto cleanups from log
        if (File::exists($logFile) && File::size($logFile) > 0) {
            try {
                $lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                $autoCleanups = [];
                $totalRuns = 0;

                foreach ($lines as $line) {
                    $data = json_decode($line, true);
                    if ($data && isset($data['timestamp'])) {
                        $timestamp = Carbon::parse($data['timestamp']);
                        $totalRuns++;

                        // Check if this was likely an automated run (around configured time)
                        $timeParts = explode(':', $cleanupTime);
                        $targetHour = (int)$timeParts[0];
                        $targetMinute = (int)$timeParts[1];

                        if (
                            $timestamp->hour == $targetHour &&
                            $timestamp->minute >= $targetMinute - 5 &&
                            $timestamp->minute <= $targetMinute + 5
                        ) {
                            $autoCleanups[] = [
                                'timestamp' => $data['timestamp'],
                                'human' => $timestamp->diffForHumans(),
                                'formatted' => $timestamp->format('M j, Y \a\t g:i A'),
                                'files_cleaned' => $data['total_files'] ?? 0,
                                'space_freed' => $this->formatBytes($data['total_size'] ?? 0),
                                'was_successful' => true
                            ];
                        }
                    }
                }

                $schedulerInfo['total_scheduled_runs'] = count($autoCleanups);
                $schedulerInfo['recent_auto_runs'] = array_slice(array_reverse($autoCleanups), 0, 5);

                if (!empty($autoCleanups)) {
                    $lastAutoCleanup = end($autoCleanups);
                    $schedulerInfo['last_auto_run'] = $lastAutoCleanup;

                    // Check if last auto run was recent (within 25 hours)
                    $lastRunTime = Carbon::parse($lastAutoCleanup['timestamp']);
                    if ($lastRunTime->diffInHours() <= 25 && $isEnabled) {
                        $schedulerInfo['status'] = 'running_successfully';
                    } else if ($isEnabled) {
                        $schedulerInfo['status'] = 'attention_needed';
                    }
                }
            } catch (\Exception $e) {
                Log::warning('Error analyzing scheduler status: ' . $e->getMessage());
                $schedulerInfo['status'] = 'error';
            }
        }

        return $schedulerInfo;
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

    public function getSchedulerSettings()
    {
        return response()->json([
            'enabled' => SchedulerSetting::isCacheCleanupEnabled(),
            'time' => SchedulerSetting::getCacheCleanupTime(),
            'timezone' => config('app.timezone')
        ]);
    }

    public function updateSchedulerSettings(Request $request)
    {
        $request->validate([
            'enabled' => 'required|boolean',
            'time' => 'required|string|date_format:H:i'
        ]);

        try {
            SchedulerSetting::setValue('cache_cleanup_enabled', $request->enabled ? 'true' : 'false');
            SchedulerSetting::setValue('cache_cleanup_time', $request->time);

            return back()->with('success', 'Scheduler settings updated successfully');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to update scheduler settings: ' . $e->getMessage()]);
        }
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
}
