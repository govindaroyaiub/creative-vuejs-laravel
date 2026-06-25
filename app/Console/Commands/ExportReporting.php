<?php

namespace App\Console\Commands;

use App\Models\ReportDay;
use App\Models\ReportSetting;
use Illuminate\Console\Command;

/**
 * Dump the reporting tables to a single JSON file so the data can travel between
 * machines (commit it to git, or copy it over) — git only moves code, not DB rows.
 *
 *   php artisan reporting:export                 # → database/reporting-export.json
 *   php artisan reporting:export path/to/out.json
 *
 * Load it on the other machine with `php artisan reporting:import`.
 */
class ExportReporting extends Command
{
    protected $signature = 'reporting:export {path=database/reporting-export.json}';

    protected $description = 'Export report_days + report_settings to a JSON file';

    public function handle(): int
    {
        $path = $this->argument('path');
        if (! str_starts_with($path, '/')) $path = base_path($path);

        $days = ReportDay::orderBy('site')->orderBy('date')->get()->map(fn ($r) => [
            'site' => $r->site,
            'date' => $r->date->format('Y-m-d'),
            'revenue' => $r->revenue,
            'impressions' => $r->impressions,
            'total_ad_requests' => $r->total_ad_requests,
            'analytics' => $r->analytics,
            'impressions_sold' => $r->impressions_sold,
        ])->all();

        // Stamp this machine as in-sync with the snapshot it's writing, so it won't
        // later flag its own export as "new data" to import.
        $exportedAt = now()->toDateTimeString();
        ReportSetting::put('synced_at', $exportedAt);

        $settings = ReportSetting::all()->mapWithKeys(fn ($s) => [$s->key => $s->value])->all();

        $payload = [
            'exported_at' => $exportedAt,
            'settings' => $settings,
            'days' => $days,
        ];

        $dir = dirname($path);
        if (! is_dir($dir)) mkdir($dir, 0775, true);
        file_put_contents($path, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        $this->info('Exported ' . count($days) . ' day-rows and ' . count($settings) . " setting(s) to {$path}");

        return self::SUCCESS;
    }
}
