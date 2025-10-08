<?php
// filepath: /Users/govinda/Desktop/creative-vuejs-laravel/app/Services/CompressionCountService.php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

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
        $currentMonth = now()->format('Y-m');

        // Auto-reset if month has changed
        if (isset($data['month']) && $data['month'] !== $currentMonth) {
            Log::info('Auto-resetting TinyPNG count for new month', [
                'old_month' => $data['month'],
                'new_month' => $currentMonth,
                'old_count' => $data['current_month_count'] ?? 0
            ]);

            $data = $this->resetForNewMonth($data);
        }

        return [
            'count' => $data['current_month_count'] ?? 0,
            'total_all_time' => $data['total_all_time'] ?? 0,
            'month' => $data['month'] ?? $currentMonth,
            'last_reset' => $data['last_reset'] ?? now()->toDateString(),
            'remaining' => max(0, 500 - ($data['current_month_count'] ?? 0)),
            'monthly_history' => $data['monthly_history'] ?? []
        ];
    }

    /**
     * Increment compression count
     */
    public function incrementCount(): array
    {
        $data = $this->getCompressionData();

        // Check if we need to reset for new month (double-check)
        $currentMonth = now()->format('Y-m');
        if ($data['month'] !== $currentMonth) {
            $data = $this->resetForNewMonth();
        }

        // Increment counters
        $data['count']++;
        $data['total_all_time']++;

        // Update the file data
        $fileData = json_decode(File::get($this->filePath), true);
        $fileData['current_month_count'] = $data['count'];
        $fileData['total_all_time'] = $data['total_all_time'];
        $fileData['last_compression'] = now()->toDateTimeString();
        $fileData['remaining'] = max(0, 500 - $data['count']);

        File::put($this->filePath, json_encode($fileData, JSON_PRETTY_PRINT));

        return [
            'count' => $data['count'],
            'total_all_time' => $data['total_all_time'],
            'remaining' => max(0, 500 - $data['count'])
        ];
    }

    /**
     * Reset count for new month
     */
    private function resetForNewMonth(array $oldData = null): array
    {
        $currentMonth = now()->format('Y-m');
        $oldData = $oldData ?? json_decode(File::get($this->filePath), true);

        // Save previous month's data to history
        $monthlyHistory = $oldData['monthly_history'] ?? [];
        if (isset($oldData['month'], $oldData['current_month_count']) && $oldData['current_month_count'] > 0) {
            $monthlyHistory[$oldData['month']] = [
                'count' => $oldData['current_month_count'],
                'reset_date' => now()->toDateString(),
                'days_active' => $this->calculateActiveDays($oldData)
            ];
        }

        $newData = [
            'current_month_count' => 0,
            'total_all_time' => $oldData['total_all_time'] ?? 0,
            'month' => $currentMonth,
            'last_reset' => now()->toDateString(),
            'remaining' => 500,
            'reset_reason' => 'new_month',
            'reset_at' => now()->toDateTimeString(),
            'monthly_history' => $monthlyHistory,
            'created_at' => $oldData['created_at'] ?? now()->toDateTimeString()
        ];

        File::put($this->filePath, json_encode($newData, JSON_PRETTY_PRINT));

        Log::info('TinyPNG count reset for new month', [
            'old_month' => $oldData['month'] ?? 'unknown',
            'new_month' => $currentMonth,
            'old_count' => $oldData['current_month_count'] ?? 0,
            'total_all_time' => $newData['total_all_time']
        ]);

        return [
            'count' => 0,
            'total_all_time' => $newData['total_all_time'],
            'month' => $currentMonth,
            'last_reset' => now()->toDateString(),
            'remaining' => 500,
            'monthly_history' => $monthlyHistory
        ];
    }

    /**
     * Initialize the file if it doesn't exist
     */
    private function initializeFile(): void
    {
        $currentMonth = now()->format('Y-m');

        $initialData = [
            'current_month_count' => 0,
            'total_all_time' => 0,
            'month' => $currentMonth,
            'created_at' => now()->toDateTimeString(),
            'last_reset' => now()->toDateString(),
            'remaining' => 500,
            'monthly_history' => [],
            'version' => '2.0'
        ];

        File::put($this->filePath, json_encode($initialData, JSON_PRETTY_PRINT));

        Log::info('TinyPNG count file initialized', [
            'month' => $currentMonth,
            'file_path' => $this->filePath
        ]);
    }

    /**
     * Manual reset (for testing or admin purposes)
     */
    public function resetCount(): void
    {
        $oldData = json_decode(File::get($this->filePath), true);

        $data = [
            'current_month_count' => 0,
            'total_all_time' => $oldData['total_all_time'] ?? 0, // Keep all-time count
            'month' => now()->format('Y-m'),
            'last_reset' => now()->toDateString(),
            'remaining' => 500,
            'reset_reason' => 'manual_reset',
            'reset_at' => now()->toDateTimeString(),
            'monthly_history' => $oldData['monthly_history'] ?? [],
            'created_at' => $oldData['created_at'] ?? now()->toDateTimeString()
        ];

        File::put($this->filePath, json_encode($data, JSON_PRETTY_PRINT));

        Log::info('TinyPNG count manually reset', [
            'old_count' => $oldData['current_month_count'] ?? 0,
            'total_all_time_preserved' => $data['total_all_time']
        ]);
    }

    /**
     * Check if user has remaining compressions
     */
    public function hasRemainingCompressions(int $needed = 1): bool
    {
        $data = $this->getCompressionData();
        return ($data['count'] + $needed) <= 500;
    }

    /**
     * Get monthly statistics
     */
    public function getMonthlyStats(): array
    {
        $data = $this->getCompressionData();

        return [
            'current_month' => [
                'month' => $data['month'],
                'count' => $data['count'],
                'remaining' => $data['remaining']
            ],
            'all_time_total' => $data['total_all_time'],
            'monthly_history' => $data['monthly_history'],
            'average_per_month' => $this->calculateAveragePerMonth($data['monthly_history'], $data['count'])
        ];
    }

    /**
     * Calculate days active in a month
     */
    private function calculateActiveDays(array $data): int
    {
        if (!isset($data['created_at']) || !isset($data['last_compression'])) {
            return 1;
        }

        $start = \Carbon\Carbon::parse($data['created_at']);
        $end = \Carbon\Carbon::parse($data['last_compression']);

        return max(1, $start->diffInDays($end) + 1);
    }

    /**
     * Calculate average compressions per month
     */
    private function calculateAveragePerMonth(array $history, int $currentCount): float
    {
        $totalMonths = count($history);
        if ($currentCount > 0) $totalMonths++; // Include current month if active

        if ($totalMonths === 0) return 0;

        $totalCompressions = array_sum(array_column($history, 'count')) + $currentCount;

        return round($totalCompressions / $totalMonths, 1);
    }
}
