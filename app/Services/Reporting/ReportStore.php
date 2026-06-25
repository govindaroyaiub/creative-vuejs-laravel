<?php

namespace App\Services\Reporting;

use App\Models\ReportDay;
use App\Models\ReportSetting;

/**
 * Persistence layer for the reporting store. Replaces the Express app's per-month
 * JSON files (data/months/YYYY-MM.json) with the report_days / report_settings
 * tables, while exposing the same merged in-memory store shape the rest of the
 * pipeline (StoreMerger, CsvGenerator, Verifier) expects.
 */
class ReportStore
{
    public const DEFAULT_OGURY_RATE = 0.85;

    /** A blank store with every site present and config loaded. */
    public static function empty(): array
    {
        $store = ['sites' => [], 'config' => []];
        foreach (array_keys(Reporting::SITES) as $id) {
            $store['sites'][$id] = ['days' => []];
        }
        return $store;
    }

    /** Load the full store from the database. */
    public static function load(): array
    {
        $store = self::empty();
        $store['config'] = [
            'oguryRate' => ReportSetting::get('oguryRate', self::DEFAULT_OGURY_RATE),
        ];

        foreach (ReportDay::all() as $row) {
            $site = $row->site;
            if (! isset($store['sites'][$site])) $store['sites'][$site] = ['days' => []];
            $k = $row->date->format('Y-m-d');
            $day = [
                'dateKey' => $k,
                'revenue' => $row->revenue ?? [],
            ];
            if ($row->impressions !== null) $day['impressions'] = $row->impressions;
            if ($row->analytics !== null) $day['analytics'] = $row->analytics;
            if ($site === 'f1maximaal') {
                $day['totalAdRequests'] = $row->total_ad_requests;
                $day['impressionsSold'] = $row->impressions_sold;
            }
            $store['sites'][$site]['days'][$k] = $day;
        }

        return $store;
    }

    /** Persist the store. Upserts every present day; config is written too. */
    public static function save(array $store): void
    {
        if (isset($store['config']['oguryRate'])) {
            ReportSetting::put('oguryRate', $store['config']['oguryRate']);
        }

        foreach ($store['sites'] as $site => $siteStore) {
            foreach ($siteStore['days'] ?? [] as $k => $day) {
                ReportDay::updateOrCreate(
                    ['site' => $site, 'date' => $k],
                    [
                        'revenue' => $day['revenue'] ?? [],
                        'impressions' => $day['impressions'] ?? null,
                        'analytics' => $day['analytics'] ?? null,
                        'total_ad_requests' => (int) round($day['totalAdRequests'] ?? 0),
                        'impressions_sold' => (int) round($day['impressionsSold'] ?? 0),
                    ],
                );
            }
        }
    }
}
