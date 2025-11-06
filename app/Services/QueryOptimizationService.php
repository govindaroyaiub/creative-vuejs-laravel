<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class QueryOptimizationService
{
    /**
     * Enable query logging for performance monitoring
     */
    public function enableQueryLogging(): void
    {
        DB::listen(function ($query) {
            $this->logSlowQuery($query);
        });
    }

    /**
     * Log slow queries for optimization
     */
    private function logSlowQuery($query): void
    {
        $threshold = config('database.slow_query_threshold', 1000); // 1 second default

        if ($query->time > $threshold) {
            Log::warning('Slow Query Detected', [
                'sql' => $query->sql,
                'bindings' => $query->bindings,
                'time' => $query->time . 'ms',
                'connection' => $query->connectionName,
            ]);

            // Store slow queries in cache for dashboard
            $this->cacheSlowQuery($query);
        }
    }

    /**
     * Cache slow queries for performance dashboard
     */
    private function cacheSlowQuery($query): void
    {
        $cacheKey = 'slow_queries_' . date('Y-m-d');
        $slowQueries = Cache::get($cacheKey, []);

        $slowQueries[] = [
            'sql' => $query->sql,
            'bindings' => $query->bindings,
            'time' => $query->time,
            'timestamp' => now()->toISOString(),
        ];

        // Keep only last 100 slow queries per day
        if (count($slowQueries) > 100) {
            $slowQueries = array_slice($slowQueries, -100);
        }

        Cache::put($cacheKey, $slowQueries, now()->addDays(7));
    }

    /**
     * Get performance statistics
     */
    public function getPerformanceStats(): array
    {
        $cacheKey = 'slow_queries_' . date('Y-m-d');
        $slowQueries = Cache::get($cacheKey, []);

        return [
            'slow_queries_today' => count($slowQueries),
            'avg_query_time' => $this->calculateAverageQueryTime($slowQueries),
            'slowest_query_time' => $this->getSlowestQueryTime($slowQueries),
            'most_common_slow_tables' => $this->getMostCommonSlowTables($slowQueries),
            'recent_slow_queries' => array_slice($slowQueries, -10), // Last 10
        ];
    }

    /**
     * Calculate average query time
     */
    private function calculateAverageQueryTime(array $queries): float
    {
        if (empty($queries)) {
            return 0;
        }

        $totalTime = array_sum(array_column($queries, 'time'));
        return round($totalTime / count($queries), 2);
    }

    /**
     * Get slowest query time
     */
    private function getSlowestQueryTime(array $queries): float
    {
        if (empty($queries)) {
            return 0;
        }

        return max(array_column($queries, 'time'));
    }

    /**
     * Get most common slow tables
     */
    private function getMostCommonSlowTables(array $queries): array
    {
        $tables = [];

        foreach ($queries as $query) {
            preg_match_all('/(?:from|join|update|into)\s+`?(\w+)`?/i', $query['sql'], $matches);
            foreach ($matches[1] as $table) {
                $tables[$table] = ($tables[$table] ?? 0) + 1;
            }
        }

        arsort($tables);
        return array_slice($tables, 0, 5, true);
    }

    /**
     * Get optimization suggestions
     */
    public function getOptimizationSuggestions(): array
    {
        $stats = $this->getPerformanceStats();
        $suggestions = [];

        // Suggest indexes for commonly queried tables
        foreach ($stats['most_common_slow_tables'] as $table => $count) {
            if ($count > 5) {
                $suggestions[] = [
                    'type' => 'index',
                    'table' => $table,
                    'message' => "Consider adding indexes to {$table} table ({$count} slow queries)",
                    'priority' => 'high'
                ];
            }
        }

        // Suggest eager loading for N+1 problems
        if ($stats['slow_queries_today'] > 20) {
            $suggestions[] = [
                'type' => 'eager_loading',
                'message' => 'High number of slow queries detected. Review for N+1 query problems.',
                'priority' => 'medium'
            ];
        }

        // Suggest query optimization
        if ($stats['avg_query_time'] > 500) {
            $suggestions[] = [
                'type' => 'query_optimization',
                'message' => 'Average query time is high. Consider optimizing complex queries.',
                'priority' => 'high'
            ];
        }

        return $suggestions;
    }

    /**
     * Analyze table sizes and suggest optimizations
     */
    public function analyzeTableSizes(): array
    {
        $tables = DB::select("
            SELECT 
                TABLE_NAME as table_name,
                ROUND(((DATA_LENGTH + INDEX_LENGTH) / 1024 / 1024), 2) AS size_mb,
                TABLE_ROWS as table_rows
            FROM information_schema.tables 
            WHERE table_schema = DATABASE()
            ORDER BY (DATA_LENGTH + INDEX_LENGTH) DESC
        ");

        $analysis = [];
        foreach ($tables as $table) {
            $analysis[] = [
                'table' => $table->table_name,
                'size_mb' => $table->size_mb,
                'rows' => $table->table_rows,
                'suggestions' => $this->getTableOptimizationSuggestions($table)
            ];
        }

        return $analysis;
    }

    /**
     * Get table-specific optimization suggestions
     */
    private function getTableOptimizationSuggestions($table): array
    {
        $suggestions = [];

        // Large tables without proper indexing
        if ($table->size_mb > 100) {
            $suggestions[] = 'Consider partitioning for tables over 100MB';
        }

        // High row count tables
        if ($table->table_rows > 100000) {
            $suggestions[] = 'Review indexes for tables with 100k+ rows';
        }

        // Activity log specific suggestions
        if ($table->table_name === 'activity_log') {
            $suggestions[] = 'Consider archiving old activity logs';
        }

        return $suggestions;
    }

    /**
     * Generate database optimization report
     */
    public function generateOptimizationReport(): array
    {
        return [
            'performance_stats' => $this->getPerformanceStats(),
            'optimization_suggestions' => $this->getOptimizationSuggestions(),
            'table_analysis' => $this->analyzeTableSizes(),
            'generated_at' => now()->toISOString(),
        ];
    }
}
