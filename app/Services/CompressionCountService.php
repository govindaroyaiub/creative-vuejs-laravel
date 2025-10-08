<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class CompressionCountService
{
    private string $filePath;

    public function __construct()
    {
        $this->filePath = storage_path('app/tinypng_count.json');
    }

    /**
     * Get current compression data
     */
    public function getCompressionData(): array
    {
        if (!File::exists($this->filePath)) {
            $this->initializeFile();
        }

        $data = json_decode(File::get($this->filePath), true);
        
        return [
            'count' => $data['count'] ?? 0,
            'month' => $data['month'] ?? now()->format('Y-m'),
            'last_reset' => $data['last_reset'] ?? now()->toDateString(),
            'remaining' => max(0, 500 - ($data['count'] ?? 0))
        ];
    }

    /**
     * Increment compression count
     */
    public function incrementCount(): int
    {
        $data = $this->getCompressionData();
        
        // Check if we need to reset for new month
        $currentMonth = now()->format('Y-m');
        if ($data['month'] !== $currentMonth) {
            $data = $this->resetForNewMonth();
        }

        $data['count']++;
        $data['last_compression'] = now()->toDateTimeString();

        File::put($this->filePath, json_encode($data, JSON_PRETTY_PRINT));

        return $data['count'];
    }

    /**
     * Reset count for new month
     */
    private function resetForNewMonth(): array
    {
        $data = [
            'count' => 0,
            'month' => now()->format('Y-m'),
            'last_reset' => now()->toDateString(),
            'reset_reason' => 'new_month'
        ];

        File::put($this->filePath, json_encode($data, JSON_PRETTY_PRINT));

        return $data;
    }

    /**
     * Initialize the file if it doesn't exist
     */
    private function initializeFile(): void
    {
        $initialData = [
            'count' => 0,
            'month' => now()->format('Y-m'),
            'created_at' => now()->toDateTimeString(),
            'last_reset' => now()->toDateString()
        ];

        File::put($this->filePath, json_encode($initialData, JSON_PRETTY_PRINT));
    }

    /**
     * Manual reset (for testing or admin purposes)
     */
    public function resetCount(): void
    {
        $data = [
            'count' => 0,
            'month' => now()->format('Y-m'),
            'last_reset' => now()->toDateString(),
            'reset_reason' => 'manual_reset',
            'reset_at' => now()->toDateTimeString()
        ];

        File::put($this->filePath, json_encode($data, JSON_PRETTY_PRINT));
    }

    /**
     * Check if user has remaining compressions
     */
    public function hasRemainingCompressions(int $needed = 1): bool
    {
        $data = $this->getCompressionData();
        return ($data['count'] + $needed) <= 500;
    }
}