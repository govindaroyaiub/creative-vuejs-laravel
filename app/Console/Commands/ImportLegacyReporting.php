<?php

namespace App\Console\Commands;

use App\Services\Reporting\ReportStore;
use Illuminate\Console\Command;

/**
 * One-time import of the standalone Express app's data into the reporting tables.
 *
 *   php artisan reporting:import-legacy /path/to/f1-maximaal-code/data
 *
 * Expects that directory to contain months/YYYY-MM.json files and an optional
 * config.json (for oguryRate), exactly as the Express app stored them.
 */
class ImportLegacyReporting extends Command
{
    protected $signature = 'reporting:import-legacy {dir : Path to the Express app data/ directory}';

    protected $description = 'Import legacy per-month JSON reporting data into report_days';

    public function handle(): int
    {
        $dir = rtrim($this->argument('dir'), '/');
        $monthsDir = $dir . '/months';

        if (! is_dir($monthsDir)) {
            $this->error("No months/ directory found at {$monthsDir}");
            return self::FAILURE;
        }

        $store = ReportStore::empty();

        // Config (oguryRate)
        $configFile = $dir . '/config.json';
        if (is_file($configFile)) {
            $config = json_decode(file_get_contents($configFile), true) ?: [];
            if (isset($config['oguryRate'])) $store['config']['oguryRate'] = $config['oguryRate'];
        }

        // Merge every month file into the store.
        $files = glob($monthsDir . '/*.json') ?: [];
        $dayCount = 0;
        foreach ($files as $file) {
            $month = json_decode(file_get_contents($file), true);
            if (! is_array($month) || ! isset($month['sites'])) continue;
            foreach ($month['sites'] as $site => $siteData) {
                if (! isset($store['sites'][$site])) $store['sites'][$site] = ['days' => []];
                foreach ($siteData['days'] ?? [] as $k => $day) {
                    $store['sites'][$site]['days'][$k] = $day;
                    $dayCount++;
                }
            }
        }

        $this->info("Read " . count($files) . " month file(s), {$dayCount} day-rows. Saving…");
        ReportStore::save($store);
        $this->info('Done.');

        return self::SUCCESS;
    }
}
