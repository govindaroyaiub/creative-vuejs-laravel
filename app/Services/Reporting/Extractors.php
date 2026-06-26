<?php

namespace App\Services\Reporting;

/**
 * One method per partner/report file, ported 1:1 from server.js. Each returns a
 * list of per-day rows with a CarbonImmutable 'date' plus numeric fields. Value
 * columns are read through Reporting::pick() so a "(EUR)"-style header rename
 * doesn't silently zero a partner.
 */
class Extractors
{
    /**
     * @param string $path     Absolute path to the file (CSV or XLSX both work).
     * @param string $filename Original filename — used to infer the site fallback.
     */
    public static function adhese(string $path, string $filename): array
    {
        $fname = mb_strtolower($filename);

        // TG / FL are checked first because some F1 filenames also contain 'adhese
        // gateway'. Anything that isn't explicitly TG or FL defaults to F1Maximaal
        // so a plainly-named file like "Adhese.csv" still routes correctly.
        if (str_contains($fname, 'adhese tg') || str_contains($fname, 'adhese topgear')) {
            $fallbackSite = 'topgear.nl';
        } elseif (str_contains($fname, 'adhese fl') || str_contains($fname, 'adhese festileaks')) {
            $fallbackSite = 'festileaks.com';
        } else {
            $fallbackSite = 'f1maximaal.nl';
        }

        // SpreadsheetReader::rows() uses PhpSpreadsheet's IOFactory, which handles
        // both CSV and XLSX transparently — fixing the silent failure when the
        // Adhese platform exports XLSX instead of CSV.
        $rows = SpreadsheetReader::rows($path);

        $out = [];
        foreach ($rows as $r) {
            // pick() is case-insensitive and strips trailing "(EUR)"-style qualifiers.
            $date = Reporting::parseDate(Reporting::pick($r, 'date') ?? '');
            if (! $date) continue;

            // Try several common column names the Adhese platform uses for the site.
            $siteRaw = Reporting::pick($r, 'site', 'domain', 'publisher', 'website') ?? '';

            $out[] = [
                'date'    => $date,
                'site'    => mb_strtolower((string) $siteRaw) ?: $fallbackSite,
                'revenue' => Reporting::stripNum(Reporting::pick($r, 'Paid Revenue', 'Revenue', 'Paid revenue')),
            ];
        }

        return $out;
    }

    public static function analytics(string $csv): array
    {
        $out = [];
        foreach (SpreadsheetReader::csvRows($csv) as $r) {
            $d = Reporting::parseDate($r['Date'] ?? $r['date'] ?? '');
            if (! $d) continue;
            $out[] = [
                'date' => $d,
                'views' => Reporting::stripNum($r['Views'] ?? ''),
                'activeUsers' => Reporting::stripNum($r['Active users'] ?? ''),
                'viewsPerUser' => Reporting::stripNum($r['Views per active user'] ?? ''),
                'avgEngagement' => Reporting::stripNum($r['Average engagement time per active user'] ?? ''),
                'eventCount' => Reporting::stripNum($r['Event count'] ?? ''),
                'keyEvents' => Reporting::stripNum($r['Key events'] ?? ''),
                'totalRevenue' => Reporting::stripNum($r['Total revenue'] ?? ''),
            ];
        }

        return $out;
    }

    public static function seedtag(string $path, string $siteId): array
    {
        $domain = Reporting::SITES[$siteId]['domain'];
        $byDate = [];
        foreach (SpreadsheetReader::rows($path) as $r) {
            if (mb_strtolower((string) ($r['Publisher'] ?? '')) !== $domain) continue;
            $d = Reporting::parseDate($r['Date'] ?? ''); if (! $d) continue;
            $k = Reporting::dateKey($d);
            $byDate[$k] ??= ['date' => $d, 'impressions' => 0.0, 'revenue' => 0.0];
            $byDate[$k]['impressions'] += Reporting::stripNum(Reporting::pick($r, 'Impressions'));
            $byDate[$k]['revenue'] += Reporting::stripNum(Reporting::pick($r, 'Revenue'));
        }

        return array_values($byDate);
    }

    public static function teads(string $path, string $siteId): array
    {
        $domain = Reporting::SITES[$siteId]['domain'];
        $byDate = [];
        foreach (SpreadsheetReader::rows($path) as $r) {
            if (mb_strtolower((string) ($r['Websites & Apps'] ?? '')) !== $domain) continue;
            $d = Reporting::parseDate($r['Day'] ?? ''); if (! $d) continue;
            $k = Reporting::dateKey($d);
            $byDate[$k] ??= ['date' => $d, 'impressions' => 0.0, 'revenue' => 0.0];
            $byDate[$k]['impressions'] += Reporting::stripNum(Reporting::pick($r, 'Sold Impressions'));
            $byDate[$k]['revenue'] += Reporting::stripNum(Reporting::pick($r, 'Estimated Earnings in EUR', 'Estimated Earnings'));
        }

        return array_values($byDate);
    }

    public static function showheroes(string $path, string $siteId): array
    {
        $domain = Reporting::SITES[$siteId]['domain'];
        $byDate = [];
        foreach (SpreadsheetReader::rows($path) as $r) {
            if (mb_strtolower((string) ($r['Site'] ?? '')) !== $domain) continue;
            $dv = $r['Date and Time'] ?? '';
            if ($dv === '' || mb_strtolower((string) $dv) === 'totals') continue;
            $d = Reporting::parseDate($dv); if (! $d) continue;
            $k = Reporting::dateKey($d);
            $byDate[$k] ??= ['date' => $d, 'impressions' => 0.0, 'revenue' => 0.0];
            $byDate[$k]['impressions'] += Reporting::stripNum(Reporting::pick($r, 'Ad Impressions'));
            $byDate[$k]['revenue'] += Reporting::stripNum(Reporting::pick($r, 'Premium Revenue'));
        }

        return array_values($byDate);
    }

    public static function gam(string $path, string $siteId): array
    {
        $prefix = Reporting::SITES[$siteId]['gamPrefix'];
        $byDate = [];
        foreach (SpreadsheetReader::rows($path) as $r) {
            if (! str_starts_with((string) ($r['Ad unit (all levels)'] ?? ''), $prefix)) continue;
            $d = Reporting::parseDate($r['Date'] ?? ''); if (! $d) continue;
            $k = Reporting::dateKey($d);
            $byDate[$k] ??= ['date' => $d, 'revenue' => 0.0];
            $byDate[$k]['revenue'] += Reporting::stripNum(Reporting::pick($r, 'AdSense revenue'))
                + Reporting::stripNum(Reporting::pick($r, 'Ad Exchange revenue'));
        }

        return array_values($byDate);
    }

    public static function adform(string $path, string $siteId): array
    {
        $prefix = Reporting::SITES[$siteId]['adformPrefix'];
        if (! in_array('Sheet', SpreadsheetReader::sheetNames($path), true)) return [];
        $rows = SpreadsheetReader::matrix($path, 'Sheet');
        if (count($rows) < 4) return [];

        $headers = array_map(fn ($h) => trim((string) $h), $rows[2]);
        $lc = array_map('strtolower', $headers);
        $ci = fn (string $name) => array_search(strtolower($name), $lc, true);
        $dateIdx = $ci('Date');
        $placeIdx = $ci('Placement');
        $impIdx = $ci('Impressions');
        $revIdx = -1;
        foreach ($lc as $i => $h) {
            if (str_contains($h, 'revenue') && ! str_contains($h, 'available')) { $revIdx = $i; break; }
        }

        $byDate = [];
        for ($i = 3; $i < count($rows); $i++) {
            $row = $rows[$i];
            if (! str_contains((string) ($row[$placeIdx] ?? ''), $prefix)) continue;
            $d = Reporting::parseDate($row[$dateIdx] ?? ''); if (! $d) continue;
            $k = Reporting::dateKey($d);
            $byDate[$k] ??= ['date' => $d, 'impressions' => 0.0, 'revenue' => 0.0];
            $byDate[$k]['impressions'] += Reporting::stripNum($row[$impIdx] ?? '');
            $byDate[$k]['revenue'] += Reporting::stripNum($revIdx >= 0 ? ($row[$revIdx] ?? '') : '');
        }

        return array_values($byDate);
    }

    public static function ogury(string $path, string $siteId, float $rate = 0.85): array
    {
        $assetMatch = mb_strtolower(Reporting::SITES[$siteId]['oguryAsset']);
        $rows = SpreadsheetReader::rows($path, ['contains' => 'statistics', 'fallbackIndex' => 1]);
        $byDate = [];
        foreach ($rows as $r) {
            if (! str_contains(mb_strtolower((string) ($r['Asset'] ?? '')), $assetMatch)) continue;
            $d = Reporting::parseDate($r['Date'] ?? ''); if (! $d) continue;
            $k = Reporting::dateKey($d);
            $byDate[$k] ??= ['date' => $d, 'impressions' => 0.0, 'revenue' => 0.0];
            $byDate[$k]['impressions'] += Reporting::stripNum(Reporting::pick($r, 'Impressions'));
            $byDate[$k]['revenue'] += Reporting::stripNum(Reporting::pick($r, 'Revenues', 'Revenue')) * $rate;
        }

        return array_values($byDate);
    }

    public static function gamF1m(string $path): array
    {
        $byDate = [];
        foreach (SpreadsheetReader::rows($path) as $r) {
            $d = Reporting::parseDate($r['Date'] ?? ''); if (! $d) continue;
            $k = Reporting::dateKey($d);
            $byDate[$k] = [
                'date' => $d,
                'totalAdRequests' => Reporting::stripNum(Reporting::pick($r, 'Total ad requests')),
                'gamImpressions' => Reporting::stripNum(Reporting::pick($r, 'Ad Exchange impressions'))
                    + Reporting::stripNum(Reporting::pick($r, 'AdSense impressions')),
            ];
        }

        return array_values($byDate);
    }

    public static function outbrain(string $csv): array
    {
        $byDate = [];
        foreach (SpreadsheetReader::csvRows($csv) as $r) {
            $pub = mb_strtolower((string) ($r['Publisher'] ?? ''));
            if ($pub !== '' && ! str_contains($pub, 'f1maximaal')) continue;
            $d = Reporting::parseDate($r['Day'] ?? ''); if (! $d) continue;
            $byDate[Reporting::dateKey($d)] = [
                'date' => $d,
                'impressions' => Reporting::stripNum(Reporting::pick($r, 'Paid Pageviews')),
                'revenue' => Reporting::stripNum(Reporting::pick($r, 'Revenue')),
            ];
        }

        return array_values($byDate);
    }

    public static function impressionsF1(string $path): array
    {
        $byDate = [];
        foreach (SpreadsheetReader::rows($path) as $r) {
            $d = Reporting::parseDate($r['Date'] ?? ''); if (! $d) continue;
            $byDate[Reporting::dateKey($d)] = [
                'date' => $d,
                'adhese' => Reporting::stripNum(Reporting::pick($r, 'Adhese')),
            ];
        }

        return array_values($byDate);
    }

    public static function preferredDeals(string $path): array
    {
        $byDate = [];
        foreach (SpreadsheetReader::rows($path) as $r) {
            $d = Reporting::parseDate($r['Date'] ?? ''); if (! $d) continue;
            $k = Reporting::dateKey($d);
            $byDate[$k] ??= ['date' => $d, 'impressions' => 0.0, 'revenue' => 0.0];
            $byDate[$k]['impressions'] += Reporting::stripNum(Reporting::pick($r, 'Ad server impressions'));
            $byDate[$k]['revenue'] += Reporting::stripNum(Reporting::pick($r, 'Ad server CPM and CPC revenue', 'Total revenue'));
        }

        return array_values($byDate);
    }
}
