<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\VirusScanService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Config;

beforeEach(function () {
    Storage::fake('local');
    $this->virusScanner = new VirusScanService();
});

describe('VirusScanService Configuration', function () {
    it('initializes with correct configuration', function () {
        expect($this->virusScanner)->toBeInstanceOf(VirusScanService::class);
    });

    it('detects if ClamAV is available', function () {
        Config::set('services.virus_scanning.enabled', true);
        Config::set('services.virus_scanning.clamscan_path', 'clamscan');

        // Mock process response for availability check
        Process::fake([
            'clamscan --version' => Process::result('ClamAV 1.0.0', 0)
        ]);

        expect($this->virusScanner->isAvailable())->toBeTrue();
    });

    it('returns false when ClamAV is not available', function () {
        Config::set('services.virus_scanning.enabled', false);

        expect($this->virusScanner->isAvailable())->toBeFalse();
    });
});

describe('VirusScanService File Scanning', function () {
    it('scans clean file successfully', function () {
        Config::set('services.virus_scanning.enabled', true);
        Config::set('services.virus_scanning.clamscan_path', 'clamscan');

        // Create a test file
        $filePath = Storage::path('test-clean-file.txt');
        Storage::put('test-clean-file.txt', 'This is a clean test file');

        // Mock ClamAV response for clean file
        Process::fake([
            "clamscan --no-summary --infected {$filePath}" => Process::result(
                "{$filePath}: OK",
                0
            )
        ]);

        $result = $this->virusScanner->scanFile($filePath);

        expect($result)->toHaveKeys(['clean', 'message', 'scan_time']);
        expect($result['clean'])->toBeTrue();
        expect($result['message'])->toContain('File is clean');
    });

    it('detects infected file', function () {
        Config::set('services.virus_scanning.enabled', true);
        Config::set('services.virus_scanning.clamscan_path', 'clamscan');

        $filePath = Storage::path('test-infected-file.txt');
        Storage::put('test-infected-file.txt', 'X5O!P%@AP[4\PZX54(P^)7CC)7}$EICAR-STANDARD-ANTIVIRUS-TEST-FILE!$H+H*');

        // Mock ClamAV response for infected file
        Process::fake([
            "clamscan --no-summary --infected {$filePath}" => Process::result(
                "{$filePath}: Eicar-Test-Signature FOUND",
                1
            )
        ]);

        $result = $this->virusScanner->scanFile($filePath);

        expect($result['clean'])->toBeFalse();
        expect($result['message'])->toContain('VIRUS DETECTED');
        expect($result['message'])->toContain('Eicar-Test-Signature');
    });

    it('handles scan errors gracefully', function () {
        Config::set('services.virus_scanning.enabled', true);
        Config::set('services.virus_scanning.clamscan_path', 'clamscan');

        $filePath = Storage::path('nonexistent-file.txt');

        // Mock ClamAV error response
        Process::fake([
            "clamscan --no-summary --infected {$filePath}" => Process::result(
                "ERROR: Can't access file {$filePath}",
                2
            )
        ]);

        $result = $this->virusScanner->scanFile($filePath);

        expect($result['clean'])->toBeFalse();
        expect($result['message'])->toContain('Scan error');
    });

    it('returns safe result when scanning is disabled', function () {
        Config::set('services.virus_scanning.enabled', false);

        $filePath = Storage::path('test-file.txt');
        Storage::put('test-file.txt', 'Test content');

        $result = $this->virusScanner->scanFile($filePath);

        expect($result['clean'])->toBeTrue();
        expect($result['message'])->toContain('Virus scanning is disabled');
    });
});

describe('VirusScanService Quarantine Management', function () {
    it('quarantines infected files', function () {
        Config::set('services.virus_scanning.quarantine_path', Storage::path('quarantine'));

        $originalPath = Storage::path('infected-file.txt');
        Storage::put('infected-file.txt', 'infected content');

        $quarantinePath = $this->virusScanner->quarantineFile($originalPath, 'Test.Virus');

        expect($quarantinePath)->toContain('quarantine');
        expect(Storage::exists('quarantine/infected-file.txt.quarantined'))->toBeTrue();
        expect(Storage::exists('infected-file.txt'))->toBeFalse();
    });

    it('creates quarantine directory if it does not exist', function () {
        $quarantinePath = Storage::path('new-quarantine');
        Config::set('services.virus_scanning.quarantine_path', $quarantinePath);

        $originalPath = Storage::path('test-file.txt');
        Storage::put('test-file.txt', 'test content');

        $this->virusScanner->quarantineFile($originalPath, 'Test.Virus');

        expect(is_dir($quarantinePath))->toBeTrue();
    });

    it('lists quarantined files', function () {
        Config::set('services.virus_scanning.quarantine_path', Storage::path('quarantine'));

        // Create some quarantined files
        Storage::put('quarantine/file1.txt.quarantined', 'content1');
        Storage::put('quarantine/file2.pdf.quarantined', 'content2');
        Storage::put('quarantine/normal-file.txt', 'normal content');

        $quarantinedFiles = $this->virusScanner->listQuarantinedFiles();

        expect($quarantinedFiles)->toHaveCount(2);
        expect($quarantinedFiles[0])->toHaveKeys(['name', 'size', 'quarantined_at']);
    });

    it('deletes quarantined files', function () {
        Config::set('services.virus_scanning.quarantine_path', Storage::path('quarantine'));

        Storage::put('quarantine/delete-me.txt.quarantined', 'infected content');

        $result = $this->virusScanner->deleteQuarantinedFile('delete-me.txt.quarantined');

        expect($result)->toBeTrue();
        expect(Storage::exists('quarantine/delete-me.txt.quarantined'))->toBeFalse();
    });
});

describe('VirusScanService Database Updates', function () {
    it('updates scan statistics', function () {
        $initialStats = $this->virusScanner->getScanStatistics();

        // Simulate successful scan
        $this->virusScanner->updateScanStatistics(true, 'clean');

        $newStats = $this->virusScanner->getScanStatistics();

        expect($newStats['total_scans'])->toBe($initialStats['total_scans'] + 1);
        expect($newStats['clean_files'])->toBe($initialStats['clean_files'] + 1);
    });

    it('tracks infected file statistics', function () {
        $initialStats = $this->virusScanner->getScanStatistics();

        // Simulate infected file scan
        $this->virusScanner->updateScanStatistics(false, 'Test.Virus');

        $newStats = $this->virusScanner->getScanStatistics();

        expect($newStats['total_scans'])->toBe($initialStats['total_scans'] + 1);
        expect($newStats['infected_files'])->toBe($initialStats['infected_files'] + 1);
    });

    it('maintains scan history', function () {
        $this->virusScanner->logScanResult('test-file.txt', true, 'Clean file', 1.5);

        $history = $this->virusScanner->getScanHistory(10);

        expect($history)->toHaveCount(1);
        expect($history[0])->toHaveKeys(['filename', 'result', 'message', 'scan_time', 'scanned_at']);
        expect($history[0]['filename'])->toBe('test-file.txt');
        expect($history[0]['result'])->toBe('clean');
    });
});

describe('VirusScanService Performance', function () {
    it('measures scan time accurately', function () {
        Config::set('services.virus_scanning.enabled', true);
        Config::set('services.virus_scanning.clamscan_path', 'clamscan');

        $filePath = Storage::path('test-file.txt');
        Storage::put('test-file.txt', 'test content');

        // Mock a slow scan
        Process::fake([
            "clamscan --no-summary --infected {$filePath}" => Process::result(
                "{$filePath}: OK",
                0
            )
        ]);

        $result = $this->virusScanner->scanFile($filePath);

        expect($result['scan_time'])->toBeGreaterThan(0);
    });

    it('handles timeout scenarios', function () {
        Config::set('services.virus_scanning.enabled', true);
        Config::set('services.virus_scanning.scan_timeout', 1);

        $filePath = Storage::path('large-file.txt');
        Storage::put('large-file.txt', str_repeat('A', 10000));

        // Mock a timeout
        Process::fake([
            "clamscan --no-summary --infected {$filePath}" => Process::result(
                "Scan timeout",
                124 // Timeout exit code
            )
        ]);

        $result = $this->virusScanner->scanFile($filePath);

        expect($result['clean'])->toBeFalse();
        expect($result['message'])->toContain('Scan timeout');
    });
});
