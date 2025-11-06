<?php

namespace App\Console\Commands;

use App\Services\QueryOptimizationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class DatabaseOptimizationReport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:optimization-report {--output=console : Output format (console, json, file)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate comprehensive database optimization report';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Generating Database Optimization Report...');

        $optimizationService = new QueryOptimizationService();
        $report = $optimizationService->generateOptimizationReport();

        $outputFormat = $this->option('output');

        switch ($outputFormat) {
            case 'json':
                $this->outputJson($report);
                break;
            case 'file':
                $this->outputToFile($report);
                break;
            default:
                $this->outputToConsole($report);
                break;
        }

        return Command::SUCCESS;
    }

    /**
     * Output report to console
     */
    private function outputToConsole(array $report): void
    {
        $this->info('📊 Database Optimization Report');
        $this->line('Generated at: ' . $report['generated_at']);
        $this->line('');

        // Performance Stats
        $stats = $report['performance_stats'];
        $this->info('🚀 Performance Statistics');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Slow Queries Today', $stats['slow_queries_today']],
                ['Average Query Time', $stats['avg_query_time'] . 'ms'],
                ['Slowest Query Time', $stats['slowest_query_time'] . 'ms'],
            ]
        );

        // Most Common Slow Tables
        if (!empty($stats['most_common_slow_tables'])) {
            $this->info('🐌 Most Common Slow Tables');
            $tableData = [];
            foreach ($stats['most_common_slow_tables'] as $table => $count) {
                $tableData[] = [$table, $count];
            }
            $this->table(['Table', 'Slow Query Count'], $tableData);
        }

        // Optimization Suggestions
        if (!empty($report['optimization_suggestions'])) {
            $this->info('💡 Optimization Suggestions');
            foreach ($report['optimization_suggestions'] as $suggestion) {
                $priority = match ($suggestion['priority']) {
                    'high' => '🔴',
                    'medium' => '🟡',
                    default => '🟢'
                };
                $this->line($priority . ' ' . $suggestion['message']);
            }
        }

        // Table Analysis
        if (!empty($report['table_analysis'])) {
            $this->info('📋 Table Analysis');
            $this->table(
                ['Table', 'Size (MB)', 'Rows', 'Suggestions'],
                array_map(function ($table) {
                    return [
                        $table['table'],
                        $table['size_mb'],
                        number_format($table['rows']),
                        implode(', ', $table['suggestions'])
                    ];
                }, array_slice($report['table_analysis'], 0, 10)) // Show top 10 tables
            );
        }

        // Recent Slow Queries
        if (!empty($stats['recent_slow_queries'])) {
            $this->info('🔍 Recent Slow Queries (Last 5)');
            foreach (array_slice($stats['recent_slow_queries'], -5) as $query) {
                $this->line('Time: ' . $query['time'] . 'ms');
                $this->line('SQL: ' . substr($query['sql'], 0, 100) . '...');
                $this->line('---');
            }
        }
    }

    /**
     * Output report as JSON
     */
    private function outputJson(array $report): void
    {
        $this->line(json_encode($report, JSON_PRETTY_PRINT));
    }

    /**
     * Output report to file
     */
    private function outputToFile(array $report): void
    {
        $filename = 'database_optimization_report_' . date('Y-m-d_H-i-s') . '.json';
        $path = 'reports/' . $filename;

        Storage::disk('local')->put($path, json_encode($report, JSON_PRETTY_PRINT));

        $this->info("📄 Report saved to: storage/app/{$path}");
    }
}
