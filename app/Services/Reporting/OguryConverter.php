<?php

namespace App\Services\Reporting;

use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Converts the current Ogury "Full Report" export into the older "Report" +
 * "Statistics 1" layout some downstream tooling still expects.
 *
 * The new export is one row per (day × ad unit × country × device); the old one
 * is one row per (day × ad unit), with device as a single attribute. So we
 * aggregate away the country/bidding-provider dimensions the old format never
 * had, sum revenue/impressions/requests, recompute eCPM, and pick each ad unit's
 * dominant device. Value conventions are translated to match the old file
 * exactly (in_article → in-article, HEADER_BIDDING → header_bidding, WEB → Web,
 * devices title-cased), as is the styling (dark-red header, number formats).
 *
 * Gated behind the report_settings 'ogury_old_format' flag and applied at ZIP
 * build time, so both formats stay available without changing what's uploaded.
 */
class OguryConverter
{
    /** Human labels for the old file's Format column, keyed by Format Key. */
    private const FORMAT_LABELS = [
        'standard_banners' => 'Standard Banners',
        'footer_ad' => 'Footer Ad',
        'in-article' => 'In-article',
    ];

    /**
     * Return old-format xlsx bytes for the given new-format Ogury file, or null
     * when the file isn't the recognised "Full Report" export (caller then ships
     * the original untouched).
     */
    public static function convert(string $path): ?string
    {
        if (! is_file($path)) return null;
        try {
            if (! in_array('Full Report', SpreadsheetReader::sheetNames($path), true)) return null;
            $rows = SpreadsheetReader::rows($path, 'Full Report');
        } catch (\Throwable) {
            return null;
        }
        if (count($rows) === 0) return null;

        [$groups, $minDate, $maxDate] = self::aggregate($rows);
        if (count($groups) === 0) return null;

        $ss = new Spreadsheet();
        self::buildReportSheet($ss->getActiveSheet(), $minDate, $maxDate);
        self::buildStatisticsSheet($ss->createSheet(), $groups);
        $ss->setActiveSheetIndex(0);

        ob_start();
        (new Xlsx($ss))->save('php://output');
        $bytes = ob_get_clean();
        $ss->disconnectWorksheets();

        return $bytes ?: null;
    }

    /**
     * Collapse the detailed rows into one entry per ad unit per day.
     *
     * @return array{0: list<array<string,mixed>>, 1: ?\Carbon\CarbonInterface, 2: ?\Carbon\CarbonInterface}
     */
    private static function aggregate(array $rows): array
    {
        $agg = [];      // groupKey => summed metrics + attributes
        $devices = [];  // groupKey => [deviceKey => [imp, req, count]]
        $min = null;
        $max = null;

        foreach ($rows as $r) {
            $date = Reporting::parseDate(Reporting::pick($r, 'UTC Date', 'Date') ?? '');
            if (! $date) continue;
            $min = $min === null || $date->lt($min) ? $date : $min;
            $max = $max === null || $date->gt($max) ? $date : $max;

            $formatKey = self::normFormatKey((string) ($r['Ad unit type'] ?? ''));
            $key = implode('|', [
                $date->format('Y-m-d'), (string) ($r['Asset key'] ?? ''), (string) ($r['Ad unit id'] ?? ''), $formatKey,
            ]);

            if (! isset($agg[$key])) {
                $agg[$key] = [
                    'date' => $date,
                    'assetKey' => (string) ($r['Asset key'] ?? ''),
                    'asset' => (string) ($r['Asset name'] ?? ''),
                    'bundle' => (string) ($r['App bundle'] ?? ''),
                    'assetTypeKey' => (string) ($r['Asset type'] ?? ''),
                    'adUnitId' => (string) ($r['Ad unit id'] ?? ''),
                    'adUnit' => (string) ($r['Ad unit name'] ?? ''),
                    'formatKey' => $formatKey,
                    'integrationKey' => (string) ($r['Integration type'] ?? ''),
                    'rev' => 0.0, 'imp' => 0.0, 'req' => 0.0,
                ];
                $devices[$key] = [];
            }

            $imp = Reporting::stripNum($r['Impressions'] ?? 0);
            $req = Reporting::stripNum(Reporting::pick($r, 'Ad Requests', 'Requests') ?? 0);
            $agg[$key]['rev'] += Reporting::stripNum($r['Revenue'] ?? 0);
            $agg[$key]['imp'] += $imp;
            $agg[$key]['req'] += $req;

            $dk = self::deviceKey((string) ($r['Device type'] ?? ''));
            $devices[$key][$dk] ??= [0.0, 0.0, 0];
            $devices[$key][$dk][0] += $imp;
            $devices[$key][$dk][1] += $req;
            $devices[$key][$dk][2] += 1;
        }

        foreach ($agg as $key => &$g) {
            $g['deviceKey'] = self::dominantDevice($devices[$key]);
        }
        unset($g);

        usort($agg, fn ($a, $b) => [$a['date']->format('Y-m-d'), $a['asset'], $a['adUnitId']]
            <=> [$b['date']->format('Y-m-d'), $b['asset'], $b['adUnitId']]);

        return [array_values($agg), $min, $max];
    }

    /** Device with the most impressions (then requests, then occurrences); '' if none. */
    private static function dominantDevice(array $deviceStats): string
    {
        $best = '';
        $bestVal = [-1.0, -1.0, -1];
        foreach ($deviceStats as $dk => $v) {
            if ($v > $bestVal) { $bestVal = $v; $best = $dk; }
        }

        return $best;
    }

    private static function normFormatKey(string $type): string
    {
        return $type === 'in_article' ? 'in-article' : $type;
    }

    private static function deviceKey(string $device): string
    {
        $d = trim($device);

        return ($d === '' || strcasecmp($d, 'none') === 0) ? '' : mb_strtolower($d);
    }

    private static function buildReportSheet($sheet, $min, $max): void
    {
        $sheet->setTitle('Report');
        $sheet->setShowGridlines(false);
        $from = $min ? $min->format('Y-m-d') : '';
        $to = $max ? $max->format('Y-m-d') : '';

        $put = function (string $cell, $value, bool $bold = false, ?int $size = null) use ($sheet) {
            $sheet->setCellValueExplicit($cell, $value, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $font = $sheet->getStyle($cell)->getFont();
            if ($bold) $font->setBold(true);
            if ($size) $font->setSize($size);
        };

        $put('A1', 'Report', false, 14);
        $put('A3', 'Parameters', false, 14);
        $put('A5', 'From', true); $put('B5', $from); $put('C5', 'UTC');
        $put('A6', 'To', true);   $put('B6', $to);   $put('C6', 'UTC');
        $put('A7', 'Organizations', true); $put('B7', 'Planet Nine Media BV');
        $put('C8', 'key'); $put('D8', 'a3440f3c-ee4b-40cc-8b45-842fea7402f3');
        $put('A9', 'Groups', true); $put('B9', 'date');
        $put('C10', 'By'); $put('D10', 'day'); $put('C11', 'Limit'); $put('D11', 'unlimited');
        $put('B12', 'asset'); $put('B13', 'ad_unit');
        $put('A14', 'Metrics', true);
        foreach (['revenues', 'impressions', 'requests', 'hb_on_bid_won', 'ecpm'] as $i => $m) {
            $put('B' . (14 + $i), $m);
        }
        $put('A19', 'Orders', true); $put('B19', 'date'); $put('B20', 'asset'); $put('B21', 'ad_unit');
        $put('A23', 'Statistics', false, 14);
        $put('A25', 'Open the Statistics tabs to get your statistics.', true, 14);

        foreach (['A' => 16, 'B' => 32, 'C' => 12, 'D' => 32] as $col => $w) {
            $sheet->getColumnDimension($col)->setWidth($w);
        }
    }

    private static function buildStatisticsSheet($sheet, array $groups): void
    {
        $sheet->setTitle('Statistics 1');
        $sheet->setShowGridlines(false);

        $headers = ['Date', 'Asset Key', 'Asset', 'Asset Bundle', 'Asset Type Key', 'Asset Type',
            'Ad Unit Id', 'Ad Unit', 'Format Key', 'Format', 'Integration Type Key', 'Integration Type',
            'Device Type Key', 'Device Type', 'Revenues', 'Impressions', 'Requests', 'Hb On Bid Won', 'Ecpm'];
        foreach ($headers as $i => $h) {
            $sheet->setCellValueExplicit([$i + 1, 1], $h, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        }
        // Header style: dark-red fill, white text, text format — matching the source export.
        $hdr = $sheet->getStyle('A1:S1');
        $hdr->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFFFF'));
        $hdr->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFC00000');
        $hdr->getNumberFormat()->setFormatCode('@');

        $r = 2;
        foreach ($groups as $g) {
            $ecpm = $g['imp'] > 0 ? round($g['rev'] / $g['imp'] * 1000, 5) : null;
            $sheet->setCellValue([1, $r], ExcelDate::PHPToExcel($g['date']->toDateTime()));
            self::text($sheet, 2, $r, $g['assetKey']);
            self::text($sheet, 3, $r, $g['asset']);
            self::text($sheet, 4, $r, $g['bundle']);
            self::text($sheet, 5, $r, $g['assetTypeKey']);
            self::text($sheet, 6, $r, $g['assetTypeKey'] === 'WEB' ? 'Web' : ucfirst(mb_strtolower($g['assetTypeKey'])));
            self::text($sheet, 7, $r, $g['adUnitId']);
            self::text($sheet, 8, $r, $g['adUnit']);
            self::text($sheet, 9, $r, $g['formatKey']);
            self::text($sheet, 10, $r, self::FORMAT_LABELS[$g['formatKey']] ?? ucwords(str_replace(['_', '-'], ' ', $g['formatKey'])));
            self::text($sheet, 11, $r, mb_strtolower($g['integrationKey']));
            self::text($sheet, 12, $r, strcasecmp($g['integrationKey'], 'HEADER_BIDDING') === 0 ? 'Header Bidding' : $g['integrationKey']);
            self::text($sheet, 13, $r, $g['deviceKey']);
            self::text($sheet, 14, $r, $g['deviceKey'] === '' ? '' : ucfirst($g['deviceKey']));
            $sheet->setCellValue([15, $r], round($g['rev'], 5));
            $sheet->setCellValue([16, $r], (int) round($g['imp']));
            $sheet->setCellValue([17, $r], (int) round($g['req']));
            // Hb On Bid Won: absent from the new export — left blank.
            if ($ecpm !== null) $sheet->setCellValue([19, $r], $ecpm);
            $r++;
        }

        $last = $r - 1;
        $cur = '[$$-en-US]#,##0.00';
        $int = '###,###,###,###,###,##0';
        $sheet->getStyle("A2:A{$last}")->getNumberFormat()->setFormatCode('yyyy-mm-dd;@');
        foreach (['B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N'] as $col) {
            $sheet->getStyle("{$col}2:{$col}{$last}")->getNumberFormat()->setFormatCode('@');
        }
        $sheet->getStyle("O2:O{$last}")->getNumberFormat()->setFormatCode($cur);
        $sheet->getStyle("S2:S{$last}")->getNumberFormat()->setFormatCode($cur);
        foreach (['P', 'Q', 'R'] as $col) {
            $sheet->getStyle("{$col}2:{$col}{$last}")->getNumberFormat()->setFormatCode($int);
        }
        foreach (['A' => 16, 'B' => 16, 'O' => 16, 'P' => 16, 'S' => 16] as $col => $w) {
            $sheet->getColumnDimension($col)->setWidth($w);
        }
    }

    private static function text($sheet, int $col, int $row, string $value): void
    {
        $sheet->setCellValueExplicit([$col, $row], $value, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
    }
}
