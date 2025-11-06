<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Http\UploadedFile;

class VirusScanService
{
    protected array $config;

    public function __construct()
    {
        $this->config = config('security.virus_scanning', []);
    }

    /**
     * Scan a file for viruses using ClamAV
     */
    public function scanFile(UploadedFile $file): array
    {
        if (!$this->isEnabled()) {
            Log::info('Virus scanning is disabled');
            return ['clean' => true, 'message' => 'Scanning disabled'];
        }

        if (!$this->isClamAvAvailable()) {
            Log::warning('ClamAV is not available - skipping virus scan');
            return ['clean' => true, 'message' => 'Scanner unavailable'];
        }

        try {
            $filePath = $file->getRealPath();
            $result = $this->executeClamScan($filePath);

            Log::info('Virus scan completed', [
                'file' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
                'result' => $result
            ]);

            return $result;
        } catch (\Exception $e) {
            Log::error('Virus scan failed', [
                'file' => $file->getClientOriginalName(),
                'error' => $e->getMessage()
            ]);

            // Fail secure - reject file if scanning fails
            return [
                'clean' => false,
                'message' => 'Scan failed: ' . $e->getMessage(),
                'threat' => 'SCAN_ERROR'
            ];
        }
    }

    /**
     * Execute ClamAV scan
     */
    protected function executeClamScan(string $filePath): array
    {
        $command = $this->buildClamCommand($filePath);

        $result = Process::run($command);

        $exitCode = $result->exitCode();
        $output = trim($result->output());
        $error = trim($result->errorOutput());

        return $this->parseClamResult($exitCode, $output, $error, $filePath);
    }

    /**
     * Build ClamAV command
     */
    protected function buildClamCommand(string $filePath): string
    {
        $clamPath = $this->config['clamscan_path'] ?? 'clamscan';

        $options = [
            '--no-summary',      // Don't show summary
            '--infected',        // Only show infected files
            '--remove=no',       // Don't remove infected files
            '--bell',           // Ring bell on virus detection
            '--stdout',         // Write to stdout
        ];

        // Add database path if specified
        if (!empty($this->config['database_path'])) {
            $options[] = "--database={$this->config['database_path']}";
        }

        $optionsStr = implode(' ', $options);

        return "\"{$clamPath}\" {$optionsStr} \"{$filePath}\"";
    }

    /**
     * Parse ClamAV scan result
     */
    protected function parseClamResult(int $exitCode, string $output, string $error, string $filePath): array
    {
        switch ($exitCode) {
            case 0:
                // File is clean
                return [
                    'clean' => true,
                    'message' => 'File is clean',
                    'scanner' => 'ClamAV'
                ];

            case 1:
                // Virus found
                $threat = $this->extractThreatName($output);

                Log::critical('VIRUS DETECTED', [
                    'file' => basename($filePath),
                    'threat' => $threat,
                    'output' => $output
                ]);

                return [
                    'clean' => false,
                    'message' => "VIRUS DETECTED: {$threat}",
                    'threat' => $threat,
                    'scanner' => 'ClamAV'
                ];

            default:
                // Error occurred
                throw new \RuntimeException("ClamAV scan error (code: {$exitCode}): {$error}");
        }
    }

    /**
     * Extract threat name from ClamAV output
     */
    protected function extractThreatName(string $output): string
    {
        // ClamAV output format: "filename: threat_name FOUND"
        if (preg_match('/:\s*(.+?)\s+FOUND/', $output, $matches)) {
            return $matches[1];
        }

        return 'Unknown threat';
    }

    /**
     * Check if virus scanning is enabled
     */
    public function isEnabled(): bool
    {
        return $this->config['enabled'] ?? false;
    }

    /**
     * Check if ClamAV is available
     */
    public function isClamAvAvailable(): bool
    {
        try {
            $clamPath = $this->config['clamscan_path'] ?? 'clamscan';
            $result = Process::run("\"{$clamPath}\" --version");

            return $result->exitCode() === 0;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get ClamAV version information
     */
    public function getClamAvVersion(): ?string
    {
        try {
            $clamPath = $this->config['clamscan_path'] ?? 'clamscan';
            $result = Process::run("\"{$clamPath}\" --version");

            if ($result->successful()) {
                return trim($result->output());
            }
        } catch (\Exception $e) {
            Log::error('Failed to get ClamAV version', ['error' => $e->getMessage()]);
        }

        return null;
    }

    /**
     * Update virus definitions
     */
    public function updateDefinitions(): bool
    {
        if (!$this->isEnabled()) {
            return false;
        }

        try {
            $freshclamPath = $this->config['freshclam_path'] ?? 'freshclam';
            $result = Process::run("\"{$freshclamPath}\"");

            $success = $result->successful();

            Log::info('Virus definitions update', [
                'success' => $success,
                'output' => $result->output()
            ]);

            return $success;
        } catch (\Exception $e) {
            Log::error('Failed to update virus definitions', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Quarantine an infected file
     */
    public function quarantineFile(string $filePath): bool
    {
        $quarantineDir = $this->config['quarantine_path'] ?? storage_path('quarantine');

        if (!is_dir($quarantineDir)) {
            mkdir($quarantineDir, 0755, true);
        }

        $quarantineFile = $quarantineDir . '/' . time() . '_' . basename($filePath);

        try {
            $success = rename($filePath, $quarantineFile);

            if ($success) {
                Log::warning('File quarantined', [
                    'original' => $filePath,
                    'quarantine' => $quarantineFile
                ]);
            }

            return $success;
        } catch (\Exception $e) {
            Log::error('Failed to quarantine file', [
                'file' => $filePath,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get virus scan statistics
     */
    public function getStatistics(): array
    {
        return [
            'enabled' => $this->isEnabled(),
            'clam_available' => $this->isClamAvAvailable(),
            'version' => $this->getClamAvVersion(),
            'database_path' => $this->config['database_path'] ?? null,
            'quarantine_path' => $this->config['quarantine_path'] ?? storage_path('quarantine'),
        ];
    }
}
