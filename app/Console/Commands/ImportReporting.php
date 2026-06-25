<?php

namespace App\Console\Commands;

use App\Models\ReportDay;
use App\Models\ReportSetting;
use Illuminate\Console\Command;

/**
 * Load a reporting export (see reporting:export) into this machine's DB. Rows are
 * upserted by (site, date), settings by key — so it merges rather than wiping.
 *
 *   php artisan reporting:import                  # ← database/reporting-export.json
 *   php artisan reporting:import path/to/in.json
 */
class ImportReporting extends Command
{
    protected $signature = 'reporting:import {path=database/reporting-export.json}';

    protected $description = 'Import report_days + report_settings from a JSON export';

    public function handle(): int
    {
        $path = $this->argument('path');
        if (! str_starts_with($path, '/')) $path = base_path($path);

        if (! is_file($path)) {
            $this->error("File not found: {$path}");
            return self::FAILURE;
        }

        $data = json_decode(file_get_contents($path), true);
        if (! is_array($data)) {
            $this->error('Could not parse the export file.');
            return self::FAILURE;
        }

        foreach ($data['settings'] ?? [] as $key => $value) {
            ReportSetting::updateOrCreate(['key' => $key], ['value' => $value]);
        }

        $count = 0;
        foreach ($data['days'] ?? [] as $d) {
            ReportDay::updateOrCreate(
                ['site' => $d['site'], 'date' => $d['date']],
                [
                    'revenue' => $d['revenue'] ?? [],
                    'impressions' => $d['impressions'] ?? null,
                    'total_ad_requests' => (int) ($d['total_ad_requests'] ?? 0),
                    'analytics' => $d['analytics'] ?? null,
                    'impressions_sold' => (int) ($d['impressions_sold'] ?? 0),
                ],
            );
            $count++;
        }

        $this->info("Imported {$count} day-rows and " . count($data['settings'] ?? []) . ' setting(s).');

        return self::SUCCESS;
    }
}
