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

    /**
     * Default upload-recognition patterns per partner. A file is recognised when
     * its (lowercased) name CONTAINS any of the comma-separated needles. These are
     * user-overridable in Settings (report_settings 'file_patterns'), so a partner
     * renaming its export is fixed in the UI rather than in code.
     */
    public const DEFAULT_FILE_PATTERNS = [
        'teads' => 'report_finance',
        'ogury' => 'ogury, export-ad-units',
        'gam' => 'copy of general data download',
        'seedtag' => 'daily_report_from, revenue-export',
        'adform' => 'tg 2, tg_2',
        'showheroes' => 'topgear-',
        'analytics' => 'pages_and_screens, content_group',
        'adhese' => 'adhese',
        'outbrain' => 'current-view, all publishers',
        'preferreddeals' => 'preferred deal',
        'gam_f1m' => 'copy of f1max',
    ];

    /**
     * Map an uploaded filename to its partner/report type. Order is significant.
     * $patterns overrides DEFAULT_FILE_PATTERNS for the configurable partners.
     *
     * @param array<string,string> $patterns
     */
    public static function detectFileType(string $filename, array $patterns = []): string
    {
        $name = mb_strtolower($filename);
        $patterns = array_merge(self::DEFAULT_FILE_PATTERNS, array_filter(
            $patterns, fn ($v) => is_string($v) && trim($v) !== ''
        ));
        $matches = function (string $type) use ($name, $patterns): bool {
            foreach (explode(',', (string) ($patterns[$type] ?? '')) as $needle) {
                $needle = trim(mb_strtolower($needle));
                if ($needle !== '' && str_contains($name, $needle)) return true;
            }
            return false;
        };

        if ($matches('adhese')) return 'adhese';
        if ($matches('analytics')) return 'analytics';
        if ($matches('adform')) return 'adform';
        if ($matches('gam')) return 'gam';
        if ($matches('ogury')) return 'ogury';
        if ($matches('seedtag')) return 'seedtag';
        if ($matches('showheroes')) return 'showheroes';
        if ($matches('teads')) return 'teads';
        if ($matches('outbrain')) return 'outbrain';
        // F1 impressions master sheet — supplies Adhese impression counts (compound rule, not configurable).
        if (str_starts_with($name, 'impressions') && str_contains($name, 'f1')) return 'impressions_f1';
        // Preferred Deals must precede gam_f1m — that filename also contains "f1max".
        if ($matches('preferreddeals')) return 'preferreddeals';
        if ($matches('gam_f1m')) return 'gam_f1m';
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
