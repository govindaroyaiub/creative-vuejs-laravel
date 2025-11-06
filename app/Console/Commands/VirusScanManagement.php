<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\VirusScanService;
use Illuminate\Support\Facades\File;

class VirusScanManagement extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'virus-scan:manage 
                           {action : Action to perform: status|update|scan|quarantine-clean}
                           {--path= : Path to scan (for scan action)}
                           {--days=7 : Days to keep quarantined files (for quarantine-clean)}';

    /**
     * The console command description.
     */
    protected $description = 'Manage virus scanning operations';

    protected VirusScanService $virusScanService;

    public function __construct(VirusScanService $virusScanService)
    {
        parent::__construct();
        $this->virusScanService = $virusScanService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $action = $this->argument('action');

        switch ($action) {
            case 'status':
                $this->showStatus();
                break;
            case 'update':
                $this->updateDefinitions();
                break;
            case 'scan':
                $this->scanPath();
                break;
            case 'quarantine-clean':
                $this->cleanQuarantine();
                break;
            default:
                $this->error("Unknown action: {$action}");
                $this->info('Available actions: status, update, scan, quarantine-clean');
        }
    }

    /**
     * Show virus scanner status
     */
    protected function showStatus(): void
    {
        $this->info('🦠 Virus Scanning Status');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━');

        $stats = $this->virusScanService->getStatistics();

        $this->table(
            ['Setting', 'Value'],
            [
                ['Enabled', $stats['enabled'] ? '✅ Yes' : '❌ No'],
                ['ClamAV Available', $stats['clam_available'] ? '✅ Yes' : '❌ No'],
                ['Version', $stats['version'] ?? '❌ Not available'],
                ['Database Path', $stats['database_path'] ?? 'Default'],
                ['Quarantine Path', $stats['quarantine_path']],
            ]
        );

        if (!$stats['enabled']) {
            $this->warn('💡 To enable virus scanning, set VIRUS_SCANNING_ENABLED=true in your .env file');
        }

        if (!$stats['clam_available']) {
            $this->warn('💡 ClamAV is not available. Install it to enable virus scanning.');
            $this->info('   Ubuntu/Debian: sudo apt-get install clamav clamav-daemon');
            $this->info('   CentOS/RHEL: sudo yum install clamav clamav-update');
            $this->info('   macOS: brew install clamav');
            $this->info('   Windows: Download from https://www.clamav.net/downloads');
        }
    }

    /**
     * Update virus definitions
     */
    protected function updateDefinitions(): void
    {
        if (!$this->virusScanService->isEnabled()) {
            $this->error('Virus scanning is disabled');
            return;
        }

        if (!$this->virusScanService->isClamAvAvailable()) {
            $this->error('ClamAV is not available');
            return;
        }

        $this->info('🔄 Updating virus definitions...');

        $success = $this->virusScanService->updateDefinitions();

        if ($success) {
            $this->info('✅ Virus definitions updated successfully');
        } else {
            $this->error('❌ Failed to update virus definitions');
        }
    }

    /**
     * Scan a specific path
     */
    protected function scanPath(): void
    {
        $path = $this->option('path');

        if (!$path) {
            $path = $this->ask('Enter path to scan');
        }

        if (!$path || !File::exists($path)) {
            $this->error('Invalid path provided');
            return;
        }

        if (!$this->virusScanService->isEnabled()) {
            $this->error('Virus scanning is disabled');
            return;
        }

        $this->info("🔍 Scanning: {$path}");

        if (File::isDirectory($path)) {
            $this->scanDirectory($path);
        } else {
            $this->scanSingleFile($path);
        }
    }

    /**
     * Scan a directory
     */
    protected function scanDirectory(string $directory): void
    {
        $files = File::allFiles($directory);
        $total = count($files);
        $infected = 0;

        $this->info("Found {$total} files to scan");

        $progressBar = $this->output->createProgressBar($total);
        $progressBar->start();

        foreach ($files as $file) {
            // Create a fake UploadedFile for scanning
            $uploadedFile = new \Illuminate\Http\UploadedFile(
                $file->getPathname(),
                $file->getFilename(),
                mime_content_type($file->getPathname()),
                null,
                true
            );

            $result = $this->virusScanService->scanFile($uploadedFile);

            if (!$result['clean']) {
                $infected++;
                $this->newLine();
                $this->error("🦠 INFECTED: {$file->getPathname()} - {$result['threat']}");
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        if ($infected > 0) {
            $this->error("⚠️  {$infected} infected files found out of {$total} scanned");
        } else {
            $this->info("✅ All {$total} files are clean");
        }
    }

    /**
     * Scan a single file
     */
    protected function scanSingleFile(string $filePath): void
    {
        $uploadedFile = new \Illuminate\Http\UploadedFile(
            $filePath,
            basename($filePath),
            mime_content_type($filePath),
            null,
            true
        );

        $result = $this->virusScanService->scanFile($uploadedFile);

        if ($result['clean']) {
            $this->info("✅ File is clean");
        } else {
            $this->error("🦠 INFECTED: {$result['threat']}");
        }
    }

    /**
     * Clean old quarantined files
     */
    protected function cleanQuarantine(): void
    {
        $days = (int) $this->option('days');
        $quarantinePath = config('security.virus_scanning.quarantine_path', storage_path('quarantine'));

        if (!File::exists($quarantinePath)) {
            $this->info('No quarantine directory found');
            return;
        }

        $cutoffTime = now()->subDays($days)->timestamp;
        $files = File::files($quarantinePath);
        $cleaned = 0;

        foreach ($files as $file) {
            if ($file->getMTime() < $cutoffTime) {
                File::delete($file->getPathname());
                $cleaned++;
            }
        }

        $this->info("🧹 Cleaned {$cleaned} files older than {$days} days from quarantine");
    }
}
