<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use App\Models\SchedulerSetting;
use Carbon\Carbon;

class AutoCacheCleanup extends Command
{
    protected $signature = 'cache:auto-cleanup {--type=all : Type of cleanup (all, laravel, storage, logs, temp)}';
    protected $description = 'Automated cache cleanup system for Laravel application';

    private $cleanupStats = [
        'laravel_cache' => ['size' => 0, 'files' => 0],
        'storage_temp' => ['size' => 0, 'files' => 0],
        'logs' => ['size' => 0, 'files' => 0],
        'compiled_views' => ['size' => 0, 'files' => 0],
        'route_cache' => ['size' => 0, 'files' => 0],
        'config_cache' => ['size' => 0, 'files' => 0],
        'temp_uploads' => ['size' => 0, 'files' => 0]
    ];

    public function handle()
    {
        $type = $this->option('type');
        $startTime = microtime(true);
        $timezone = SchedulerSetting::getTimezone();

        $this->info("🚀 Starting automated cache cleanup - Type: {$type}");
        $this->info("⏰ Started at: " . now()->setTimezone($timezone)->format('Y-m-d H:i:s T'));

        try {
            switch ($type) {
                case 'laravel':
                    $this->cleanLaravelCache();
                    break;
                case 'storage':
                    $this->cleanStorageTemp();
                    break;
                case 'logs':
                    $this->cleanOldLogs();
                    break;
                case 'temp':
                    $this->cleanTempFiles();
                    break;
                case 'all':
                default:
                    $this->cleanAll();
                    break;
            }

            $endTime = microtime(true);
            $duration = round($endTime - $startTime, 2);

            $this->displayCleanupSummary($duration);
            $this->logCleanupActivity();

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("❌ Cache cleanup failed: " . $e->getMessage());

            // Log the error for dashboard display
            $this->logCleanupError($e);

            return Command::FAILURE;
        }
    }

    private function cleanAll()
    {
        $this->info("🧹 Running complete system cleanup...");

        $this->cleanLaravelCache();
        $this->cleanStorageTemp();
        $this->cleanOldLogs();
        $this->cleanTempFiles();
        $this->cleanCompiledViews();
        $this->cleanRouteCache();
        $this->cleanConfigCache();
    }

    private function cleanLaravelCache()
    {
        $this->line("🗂️  Clearing Laravel cache...");

        try {
            // Clear application cache
            Cache::flush();
            $this->cleanupStats['laravel_cache']['files']++;

            // Clear specific cache files
            $cacheFiles = [
                storage_path('framework/cache/data'),
                storage_path('framework/sessions'),
                storage_path('framework/cache')
            ];

            foreach ($cacheFiles as $path) {
                if (File::exists($path)) {
                    $size = $this->getFolderSize($path);
                    $this->cleanupStats['laravel_cache']['size'] += $size;

                    if (is_dir($path)) {
                        $files = File::allFiles($path);
                        foreach ($files as $file) {
                            File::delete($file->getPathname());
                            $this->cleanupStats['laravel_cache']['files']++;
                        }
                    }
                }
            }

            $this->info("✅ Laravel cache cleared");
        } catch (\Exception $e) {
            $this->warn("⚠️  Laravel cache cleanup warning: " . $e->getMessage());
        }
    }

    private function cleanStorageTemp()
    {
        $this->line("📁 Cleaning storage temp files...");

        $tempPaths = [
            storage_path('app/temp'),
            storage_path('app/tmp'),
            public_path('temp'),
            public_path('tmp')
        ];

        foreach ($tempPaths as $path) {
            if (File::exists($path)) {
                $size = $this->getFolderSize($path);
                $files = File::allFiles($path);

                foreach ($files as $file) {
                    // Use modification time as it's more reliable across systems
                    $fileAge = Carbon::createFromTimestamp($file->getMTime());
                    if ($fileAge->lt(now()->subHours(24))) {
                        $fileSize = $file->getSize();
                        File::delete($file->getPathname());
                        $this->cleanupStats['storage_temp']['files']++;
                        $this->cleanupStats['storage_temp']['size'] += $fileSize;
                    }
                }

                $this->cleanupStats['storage_temp']['size'] += $size;
            }
        }

        $this->info("✅ Storage temp files cleaned");
    }

    private function cleanOldLogs()
    {
        $this->line("📋 Cleaning old log files...");

        $logPath = storage_path('logs');

        if (File::exists($logPath)) {
            $logFiles = File::allFiles($logPath);

            foreach ($logFiles as $file) {
                $fileAge = Carbon::createFromTimestamp($file->getMTime());
                if ($fileAge->lt(now()->subDays(7))) {
                    $size = $file->getSize();
                    File::delete($file->getPathname());

                    $this->cleanupStats['logs']['size'] += $size;
                    $this->cleanupStats['logs']['files']++;
                }
            }
        }

        $this->info("✅ Old logs cleaned (>7 days)");
    }

    private function cleanTempFiles()
    {
        $this->line("🗃️  Cleaning temporary upload files...");

        $uploadPaths = [
            public_path('uploads/temp'),
            storage_path('app/public/temp')
        ];

        foreach ($uploadPaths as $path) {
            if (File::exists($path)) {
                $files = File::allFiles($path);

                foreach ($files as $file) {
                    $fileAge = Carbon::createFromTimestamp($file->getMTime());
                    if ($fileAge->lt(now()->subHours(12))) {
                        $size = $file->getSize();
                        File::delete($file->getPathname());

                        $this->cleanupStats['temp_uploads']['size'] += $size;
                        $this->cleanupStats['temp_uploads']['files']++;
                    }
                }
            }
        }

        $this->info("✅ Temporary upload files cleaned");
    }

    private function getFolderSize($path)
    {
        $size = 0;

        if (File::exists($path) && is_dir($path)) {
            $files = File::allFiles($path);
            foreach ($files as $file) {
                $size += $file->getSize();
            }
        }

        return $size;
    }

    private function cleanCompiledViews()
    {
        $this->line("👁️  Clearing compiled views...");

        try {
            Artisan::call('view:clear');

            $viewPath = storage_path('framework/views');
            if (File::exists($viewPath)) {
                $size = $this->getFolderSize($viewPath);
                $files = File::allFiles($viewPath);

                $this->cleanupStats['compiled_views']['size'] = $size;
                $this->cleanupStats['compiled_views']['files'] = count($files);
            }

            $this->info("✅ Compiled views cleared");
        } catch (\Exception $e) {
            $this->warn("⚠️  View cache warning: " . $e->getMessage());
        }
    }

    private function cleanRouteCache()
    {
        $this->line("🛣️  Clearing route cache...");

        try {
            Artisan::call('route:clear');
            $this->cleanupStats['route_cache']['files'] = 1;
            $this->info("✅ Route cache cleared");
        } catch (\Exception $e) {
            $this->warn("⚠️  Route cache warning: " . $e->getMessage());
        }
    }

    private function cleanConfigCache()
    {
        $this->line("⚙️  Clearing config cache...");

        try {
            Artisan::call('config:clear');
            $this->cleanupStats['config_cache']['files'] = 1;
            $this->info("✅ Config cache cleared");
        } catch (\Exception $e) {
            $this->warn("⚠️  Config cache warning: " . $e->getMessage());
        }
    }

    private function formatBytes($size)
    {
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $size > 1024; $i++) {
            $size /= 1024;
        }

        return round($size, 2) . ' ' . $units[$i];
    }

    private function displayCleanupSummary($duration)
    {
        $this->newLine();
        $this->info("📊 CLEANUP SUMMARY");
        $this->info("==================");

        $totalSize = 0;
        $totalFiles = 0;

        foreach ($this->cleanupStats as $category => $stats) {
            if ($stats['files'] > 0 || $stats['size'] > 0) {
                $this->line(sprintf(
                    "%-20s: %d files, %s",
                    ucwords(str_replace('_', ' ', $category)),
                    $stats['files'],
                    $this->formatBytes($stats['size'])
                ));

                $totalSize += $stats['size'];
                $totalFiles += $stats['files'];
            }
        }

        $this->newLine();
        $this->info("📈 TOTALS:");
        $this->info("Files cleaned: " . $totalFiles);
        $this->info("Space freed: " . $this->formatBytes($totalSize));
        $this->info("Duration: {$duration}s");
        $this->newLine();
    }

    private function logCleanupActivity()
    {
        $logData = [
            'timestamp' => now()->toISOString(),
            'stats' => $this->cleanupStats,
            'total_files' => array_sum(array_column($this->cleanupStats, 'files')),
            'total_size' => array_sum(array_column($this->cleanupStats, 'size'))
        ];

        $logFile = storage_path('logs/cache_cleanup.log');
        $logEntry = json_encode($logData) . "\n";

        File::append($logFile, $logEntry);
    }

    private function logCleanupError(\Exception $e)
    {
        $errorData = [
            'timestamp' => now()->toISOString(),
            'error' => true,
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'type' => 'scheduler_failure'
        ];

        $errorLogFile = storage_path('logs/cache_cleanup_errors.log');
        $errorEntry = json_encode($errorData) . "\n";

        File::append($errorLogFile, $errorEntry);

        // Also log to Laravel log for admin visibility
        Log::error('Cache cleanup scheduler failed', $errorData);
    }
}
