<?php

namespace App\Services\Reporting;

use Carbon\CarbonImmutable;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

/**
 * Shared constants and pure helpers for the reporting pipeline.
 *
 * Ported 1:1 from the standalone Express app (server.js) so the numbers match
 * exactly. Keep this faithful — the partner files rely on these exact prefixes,
 * date formats and column-name tolerances.
 */
class Reporting
{
    /** Sites, matched by Adform/GAM filename prefixes. */
    public const SITES = [
        'f1maximaal' => ['name' => 'F1Maximaal.nl', 'adformPrefix' => 'F1M_', 'gamPrefix' => 'VM_F1Maximaal', 'domain' => 'f1maximaal.nl', 'oguryAsset' => 'f1maximaal.nl'],
        'topgear'    => ['name' => 'TopGear.nl', 'adformPrefix' => 'TG_', 'gamPrefix' => 'VDS_Topgear', 'domain' => 'topgear.nl', 'oguryAsset' => 'topgear.nl'],
        'horses'     => ['name' => 'Horses.nl', 'adformPrefix' => 'OHO_', 'gamPrefix' => 'EHM_Eisma', 'domain' => 'horses.nl', 'oguryAsset' => 'horses.nl'],
        'festileaks' => ['name' => 'Festileaks.com', 'adformPrefix' => 'FL_', 'gamPrefix' => 'FL_Festileaks', 'domain' => 'festileaks.com', 'oguryAsset' => 'festileaks'],
    ];

    /** Partner buffer types whose uploaded file is re-saved under a canonical name. */
    public const RENAME_MAP = [
        'adform' => 'Adform',
        'gam' => 'GAM',
        'ogury' => 'Ogury',
        'seedtag' => 'SeedTag',
        'showheroes' => 'Showheroes',
        'teads' => 'Teads',
    ];

    public const ADHESE_MARKET = [
        'f1maximaal' => 'DALE-igmn',
        'topgear' => 'DALE-igmn',
        'festileaks' => 'DALE-igmn',
    ];

    public const MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

    /** Map an uploaded filename to its partner/report type. Order is significant. */
    public static function detectFileType(string $filename): string
    {
        $name = mb_strtolower($filename);

        if (str_contains($name, 'adhese gateway') || str_starts_with($name, 'adhese')) return 'adhese';
        if (str_contains($name, 'pages_and_screens') || str_contains($name, 'content_group')) return 'analytics';
        if (preg_match('/^tg[\s_]\d/', $name) || str_starts_with($name, 'tg 2')) return 'adform';
        if (str_contains($name, 'copy of general data download')) return 'gam';
        if (str_starts_with($name, 'export-ad-units') || str_starts_with($name, 'ogury')) return 'ogury';
        if (str_starts_with($name, 'revenue-export')) return 'seedtag';
        if (str_starts_with($name, 'topgear-')) return 'showheroes';
        if (str_starts_with($name, 'report_finance')) return 'teads';
        if (str_contains($name, 'current-view') || str_contains($name, 'all publishers')) return 'outbrain';
        // F1 impressions master sheet — supplies Adhese impression counts.
        if (str_starts_with($name, 'impressions') && str_contains($name, 'f1')) return 'impressions_f1';
        // Must precede the gam_f1m rule below — this filename also contains "f1max".
        if (str_contains($name, 'preferred') && str_contains($name, 'deal')) return 'preferreddeals';
        if (preg_match('/copy.*f1max/', $name) || str_contains($name, 'copy of f1maximaal')) return 'gam_f1m';
        if (str_starts_with($name, 'planetnine-report-')) return 'planetnine';
        if (str_starts_with($name, 'tg-revenue-report-')) return 'report_topgear';
        if (str_starts_with($name, 'horses-revenue-report-')) return 'report_horses';
        if (str_starts_with($name, 'festileaks-revenue-report-')) return 'report_festileaks';

        return 'unknown';
    }

    /**
     * Parse a date from any of the formats the partner files use, plus Excel
     * serial numbers. Returns a CarbonImmutable at midnight, or null.
     */
    public static function parseDate(mixed $val): ?CarbonImmutable
    {
        if ($val === null || $val === '') return null;
        $s = trim((string) $val);
        if ($s === '') return null;

        $monthIdx = function (string $tok): int {
            $t = mb_strtolower(substr($tok, 0, 3));
            foreach (self::MONTHS as $i => $m) {
                if (strtolower($m) === $t) return $i;
            }
            return -1;
        };
        $make = fn (int $y, int $mZero, int $d): ?CarbonImmutable =>
            ($mZero < 0 || $mZero > 11) ? null : CarbonImmutable::create($y, $mZero + 1, $d, 0, 0, 0);

        if (preg_match('/^([A-Za-z]+)\s+(\d+),?\s+(\d{4})$/', $s, $m)) {
            return $make((int) $m[3], $monthIdx($m[1]), (int) $m[2]);
        }
        if (preg_match('/^(\d{1,2})\s+([A-Za-z]+)\s+(\d{4})$/', $s, $m)) {
            return $make((int) $m[3], $monthIdx($m[2]), (int) $m[1]);
        }
        if (preg_match('/^(\d+)-([A-Za-z]+)-(\d{2,4})$/', $s, $m)) {
            $yr = strlen($m[3]) === 2 ? 2000 + (int) $m[3] : (int) $m[3];
            return $make($yr, $monthIdx($m[2]), (int) $m[1]);
        }
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $s, $m)) {
            return $make((int) $m[1], (int) $m[2] - 1, (int) $m[3]);
        }
        if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $s, $m)) {
            return $make((int) $m[3], (int) $m[1] - 1, (int) $m[2]);
        }
        if (preg_match('/^(\d{2})-(\d{2})-(\d{4})$/', $s, $m)) {
            return $make((int) $m[3], (int) $m[2] - 1, (int) $m[1]);
        }
        if (preg_match('/^(\d{4})(\d{2})(\d{2})$/', $s, $m)) {
            return $make((int) $m[1], (int) $m[2] - 1, (int) $m[3]);
        }
        if (preg_match('/^\d+(\.\d+)?$/', $s)) {
            try {
                $dt = ExcelDate::excelToDateTimeObject((float) $s);
                return CarbonImmutable::create((int) $dt->format('Y'), (int) $dt->format('n'), (int) $dt->format('j'), 0, 0, 0);
            } catch (\Throwable) {
                return null;
            }
        }

        return null;
    }

    public static function dateKey(CarbonImmutable $d): string
    {
        return $d->format('Y-m-d');
    }

    public static function fmtAdheseDate(CarbonImmutable $d): string
    {
        return $d->day . '-' . self::MONTHS[$d->month - 1] . '-' . substr((string) $d->year, 2);
    }

    public static function monthKey(string $dateKey): string
    {
        return substr($dateKey, 0, 7);
    }

    /** Strip currency/formatting and coerce to a float (0 on empty/invalid). */
    public static function stripNum(mixed $s): float
    {
        if ($s === null || $s === '') return 0.0;
        $cleaned = preg_replace('/[€$£,\s%]/u', '', (string) $s);

        return is_numeric($cleaned) ? (float) $cleaned : 0.0;
    }

    /**
     * Read a value from a parsed row by trying several header candidates,
     * tolerant of case, whitespace and a trailing "(...)" qualifier that
     * partners add/remove — e.g. "Premium Revenue" ⇄ "Premium Revenue (EUR)".
     */
    public static function pick(array $row, string ...$candidates): mixed
    {
        $norm = fn (string $k): string => trim(preg_replace('/\s+/', ' ',
            preg_replace('/\s*\([^)]*\)\s*$/', '', mb_strtolower($k))));
        $want = array_map($norm, $candidates);
        foreach ($row as $key => $value) {
            if (in_array($norm((string) $key), $want, true)) return $value;
        }

        return null;
    }
}
