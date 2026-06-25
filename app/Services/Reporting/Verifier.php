<?php

namespace App\Services\Reporting;

use RuntimeException;

/**
 * Reconciles stored data against the Planetnine reports, ported 1:1 from
 * server.js /verify (F1Maximaal monthly) and /verify-weekly (TopGear, Horses,
 * Festileaks). Returns per-day rows of { label, pn, us, tol } checks; the
 * frontend renders the pass/fail comparison.
 */
class Verifier
{
    private const PARTNERS = [
        'adform' => 'Adform', 'gam' => 'GAM', 'ogury' => 'Ogury', 'seedtag' => 'Seedtag',
        'showheroes' => 'Showheroes', 'teads' => 'Teads', 'preferredDeals' => 'Preferred Deals',
        'outbrain' => 'Outbrain', 'adhese' => 'Adhese',
    ];

    /** F1Maximaal monthly Planetnine report. */
    public static function monthly(array $store, string $path): array
    {
        $names = SpreadsheetReader::sheetNames($path);
        if (! in_array('Trend', $names, true)) throw new RuntimeException('Could not find Trend sheet');

        $tRows = SpreadsheetReader::matrix($path, 'Trend');
        $tHdrIdx = self::findHeaderRow($tRows);
        if ($tHdrIdx < 0) throw new RuntimeException('Could not find header row in Trend sheet');
        $tHdr = array_map(fn ($h) => trim((string) $h), $tRows[$tHdrIdx]);
        $tDate = self::idx($tHdr, 'Date');
        $tSold = self::idx($tHdr, 'Impressions Sold');
        $tReq = self::idx($tHdr, 'Total Ad Requests');
        $tRev = self::idx($tHdr, 'Revenues');

        $pnTrend = [];
        for ($i = $tHdrIdx + 1; $i < count($tRows); $i++) {
            $r = $tRows[$i];
            $dv = $r[$tDate] ?? '';
            if ($dv === '' || str_starts_with((string) $dv, 'Note')) break;
            $d = Reporting::parseDate($dv); if (! $d) continue;
            $k = Reporting::dateKey($d);
            $pnTrend[$k] = [
                'impressionsSold' => Reporting::stripNum($r[$tSold] ?? ''),
                'totalAdRequests' => Reporting::stripNum($r[$tReq] ?? ''),
                'revenue' => Reporting::stripNum($r[$tRev] ?? ''),
            ];
        }

        if (in_array('Demand Partners', $names, true)) {
            $dRows = SpreadsheetReader::matrix($path, 'Demand Partners');
            $dHdrIdx = self::findHeaderRow($dRows);
            $dHdr = $dHdrIdx >= 0 ? array_map(fn ($h) => trim((string) $h), $dRows[$dHdrIdx]) : [];
            $dDate = self::idx($dHdr, 'Date');
            for ($i = $dHdrIdx + 1; $i < count($dRows); $i++) {
                $r = $dRows[$i];
                if (($r[$dDate] ?? '') === '') continue;
                $d = Reporting::parseDate($r[$dDate]); if (! $d) continue;
                $k = Reporting::dateKey($d);
                if (! isset($pnTrend[$k])) continue;
                $pnTrend[$k]['partners'] = self::readPartners($dHdr, $r);
            }
        }

        $f1Days = $store['sites']['f1maximaal']['days'] ?? [];
        ksort($pnTrend);
        $rows = [];
        foreach ($pnTrend as $k => $pn) {
            $s = $f1Days[$k] ?? null;
            $rev = $s['revenue'] ?? [];
            $checks = [
                ['label' => 'Impr. Sold', 'pn' => $pn['impressionsSold'], 'us' => $s['impressionsSold'] ?? 0, 'tol' => 50],
                ['label' => 'Ad Requests', 'pn' => $pn['totalAdRequests'], 'us' => $s['totalAdRequests'] ?? 0, 'tol' => 10],
                ['label' => 'Total Revenue', 'pn' => $pn['revenue'], 'us' => array_sum(array_map('floatval', $rev)), 'tol' => 0.5],
            ];
            if (isset($pn['partners'])) {
                foreach (self::PARTNERS as $key => $col) {
                    $checks[] = ['label' => $col, 'pn' => $pn['partners'][$key] ?? 0, 'us' => $rev[$key] ?? 0, 'tol' => 0.5];
                }
            }
            $rows[] = ['dateKey' => $k, 'checks' => $checks];
        }

        return $rows;
    }

    /** Weekly revenue report for TopGear / Horses / Festileaks. */
    public static function weekly(array $store, string $path, string $siteId): array
    {
        if (! isset(Reporting::SITES[$siteId])) throw new RuntimeException('Unknown site');
        $names = SpreadsheetReader::sheetNames($path);
        if (! in_array('Demand Partners', $names, true)) throw new RuntimeException('Could not find Demand Partners sheet');

        $dRows = SpreadsheetReader::matrix($path, 'Demand Partners');
        $dHdrIdx = self::findHeaderRow($dRows);
        if ($dHdrIdx < 0) throw new RuntimeException('Could not find header row in Demand Partners sheet');
        $dHdr = array_map(fn ($h) => trim((string) $h), $dRows[$dHdrIdx]);
        $dDate = self::idx($dHdr, 'Date');
        $totalIdx = self::idx($dHdr, 'Total');

        $pnData = [];
        for ($i = $dHdrIdx + 1; $i < count($dRows); $i++) {
            $r = $dRows[$i];
            if (($r[$dDate] ?? '') === '') continue;
            $d = Reporting::parseDate($r[$dDate]); if (! $d) continue;
            $k = Reporting::dateKey($d);
            $pnData[$k] = [
                'partners' => self::readPartners($dHdr, $r),
                'total' => $totalIdx >= 0 ? Reporting::stripNum($r[$totalIdx] ?? '') : 0,
            ];
        }

        $siteDays = $store['sites'][$siteId]['days'] ?? [];
        ksort($pnData);
        $rows = [];
        foreach ($pnData as $k => $pn) {
            $rev = $siteDays[$k]['revenue'] ?? [];
            $ourTotal = array_sum(array_map('floatval', $rev));
            $checks = [['label' => 'Total Revenue', 'pn' => $pn['total'], 'us' => $ourTotal, 'tol' => 0.5]];
            foreach (self::PARTNERS as $key => $col) {
                if (($pn['partners'][$key] ?? 0) > 0 || ($rev[$key] ?? 0) > 0) {
                    $checks[] = ['label' => $col, 'pn' => $pn['partners'][$key] ?? 0, 'us' => $rev[$key] ?? 0, 'tol' => 0.5];
                }
            }
            $rows[] = ['dateKey' => $k, 'checks' => $checks];
        }

        return ['siteName' => Reporting::SITES[$siteId]['name'], 'rows' => $rows];
    }

    private static function readPartners(array $hdr, array $row): array
    {
        $partners = [];
        foreach (self::PARTNERS as $key => $col) {
            $idx = self::idx($hdr, $col);
            $v = $idx >= 0 ? ($row[$idx] ?? 0) : 0;
            $partners[$key] = ($v === '-' || $v === '') ? 0 : Reporting::stripNum($v);
        }
        return $partners;
    }

    private static function findHeaderRow(array $rows): int
    {
        foreach ($rows as $i => $r) {
            if (trim((string) ($r[0] ?? '')) === 'Date') return $i;
        }
        return -1;
    }

    private static function idx(array $hdr, string $name): int
    {
        $i = array_search($name, $hdr, true);
        return $i === false ? -1 : $i;
    }
}
