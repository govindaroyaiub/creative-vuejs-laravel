<?php

namespace App\Services\Reporting;

use Carbon\CarbonImmutable;

/**
 * Regenerates the downloadable CSVs (Analytics + per-site Adhese), ported 1:1
 * from server.js. These are the files the Adhese/GA4 tooling re-ingests, so the
 * exact header text and number formatting must match.
 */
class CsvGenerator
{
    /** GA4 "Pages and screens: Content group" export for F1Maximaal. */
    public static function analytics(array $store, ?string $from = null, ?string $to = null): string
    {
        $daysMap = $store['sites']['f1maximaal']['days'] ?? [];
        $days = array_values($daysMap);
        usort($days, fn ($a, $b) => strcmp($a['dateKey'], $b['dateKey']));
        if ($from) $days = array_filter($days, fn ($d) => $d['dateKey'] >= $from);
        if ($to) $days = array_filter($days, fn ($d) => $d['dateKey'] <= $to);
        $days = array_values($days);
        if (count($days) === 0) return '';

        $first = $days[0]['dateKey'];
        $last = $days[count($days) - 1]['dateKey'];
        $lines = [
            '# ----------------------------------------',
            '# Pages and screens: Content group',
            '# Account: F1Maximaal',
            '# Property: F1Maximaal.nl - GA4',
            '# ----------------------------------------',
            '#',
            '# All Users',
            '# Start date: ' . str_replace('-', '', $first),
            '# End date: ' . str_replace('-', '', $last),
            'Content group,Date,Views,Active users,Views per active user,Average engagement time per active user,Event count,Key events,Total revenue,Impressions Sold,Total Ad Requests',
        ];

        foreach ($days as $d) {
            $a = $d['analytics'] ?? null;
            if (! $a) continue;
            $sold = ! empty($d['impressionsSold']) ? '"' . number_format((float) $d['impressionsSold'], 0, '.', ',') . '"' : '0';
            $req = ! empty($d['totalAdRequests']) ? '"' . number_format((float) $d['totalAdRequests'], 0, '.', ',') . '"' : '0';
            $lines[] = 'F1Maximaal.nl,' . str_replace('-', '', $d['dateKey']) . ',' .
                $a['views'] . ',' . $a['activeUsers'] . ',' . $a['viewsPerUser'] . ',' .
                $a['avgEngagement'] . ',' . $a['eventCount'] . ',' . $a['keyEvents'] . ',' .
                $a['totalRevenue'] . ',' . $sold . ',' . $req;
        }

        return implode("\n", $lines);
    }

    /** Adhese revenue upload for a single site. */
    public static function adhese(array $store, string $siteId = 'f1maximaal', ?string $from = null, ?string $to = null): string
    {
        $site = Reporting::SITES[$siteId];
        $daysMap = $store['sites'][$siteId]['days'] ?? [];
        $days = array_values($daysMap);
        usort($days, fn ($a, $b) => strcmp($a['dateKey'], $b['dateKey']));
        if ($from) $days = array_filter($days, fn ($d) => $d['dateKey'] >= $from);
        if ($to) $days = array_filter($days, fn ($d) => $d['dateKey'] <= $to);

        $lines = ['date,site,market.name,Paid Revenue'];
        $marketName = Reporting::ADHESE_MARKET[$siteId] ?? '';
        foreach ($days as $d) {
            $adhese = $d['revenue']['adhese'] ?? 0;
            if (! $adhese) continue;
            $dt = CarbonImmutable::parse($d['dateKey'] . 'T12:00:00');
            $lines[] = Reporting::fmtAdheseDate($dt) . ',' . $site['name'] . ',' . $marketName . ',' . $adhese;
        }

        return implode("\n", $lines);
    }
}
