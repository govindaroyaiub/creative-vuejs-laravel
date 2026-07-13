<?php

namespace App\Services\Reporting;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Exports the reporting dashboard table (the per-day breakdown shown on the
 * "Table" tab) for a site + date range, split into Revenues and Impressions:
 * two worksheets in the XLSX, two labelled sections in the CSV, two keyed
 * blocks in the JSON.
 *
 * Separate from ZipBuilder: that packages the raw partner source files for
 * re-upload elsewhere; this hands back the computed daily figures the dashboard
 * displays. Impression counts are only tracked for F1Maximaal, so the
 * Impressions block is blank for the other sites.
 */
class TableExporter
{
    /** Partner columns, in the same order the dashboard table renders them. */
    private const PARTNERS = [
        'adhese' => 'Adhese', 'gam' => 'GAM', 'seedtag' => 'SeedTag', 'teads' => 'Teads',
        'showheroes' => 'Showheroes', 'adform' => 'Adform', 'ogury' => 'Ogury',
        'outbrain' => 'Outbrain', 'preferredDeals' => 'Preferred Deals',
    ];

    /**
     * Build the structured export payload for one site over an optional range.
     *
     * @return array{siteId:string, siteName:string, from:?string, to:?string, isF1:bool,
     *               partners:array<string,string>, days:list<array<string,mixed>>, totals:array<string,mixed>}
     */
    public static function data(array $store, string $siteId, ?string $from = null, ?string $to = null): array
    {
        $isF1 = $siteId === 'f1maximaal';
        $map = $store['sites'][$siteId]['days'] ?? [];
        ksort($map);

        $days = [];
        $revTotals = array_fill_keys(array_keys(self::PARTNERS), 0.0);
        $impTotals = array_fill_keys(array_keys(self::PARTNERS), 0);
        $grand = 0.0;
        $soldTotal = 0;
        $requestsTotal = 0;

        foreach ($map as $k => $day) {
            if ($from && $k < $from) continue;
            if ($to && $k > $to) continue;

            $rev = $day['revenue'] ?? [];
            $imp = $day['impressions'] ?? [];
            $row = ['date' => $k, 'revenue' => [], 'impressions' => [], 'total' => 0.0];
            $dayTotal = 0.0;
            foreach (self::PARTNERS as $key => $label) {
                $v = (float) ($rev[$key] ?? 0);
                $row['revenue'][$key] = round($v, 2);
                $dayTotal += $v;
                $revTotals[$key] += $v;

                // Adhese impressions are entered by hand and may be genuinely absent (null).
                $iv = $imp[$key] ?? null;
                $row['impressions'][$key] = $iv === null ? null : (int) $iv;
                $impTotals[$key] += (int) ($iv ?? 0);
            }
            $row['total'] = round($dayTotal, 2);
            $grand += $dayTotal;

            $row['impressionsSold'] = (int) ($day['impressionsSold'] ?? 0);
            $row['totalAdRequests'] = (int) ($day['totalAdRequests'] ?? 0);
            $soldTotal += $row['impressionsSold'];
            $requestsTotal += $row['totalAdRequests'];

            $days[] = $row;
        }

        return [
            'siteId' => $siteId,
            'siteName' => Reporting::SITES[$siteId]['name'] ?? $siteId,
            'from' => $from ?: null,
            'to' => $to ?: null,
            'isF1' => $isF1,
            'partners' => self::PARTNERS,
            'days' => $days,
            'totals' => [
                'revenue' => array_map(fn ($v) => round($v, 2), $revTotals),
                'total' => round($grand, 2),
                'impressions' => $impTotals,
                'impressionsSold' => $soldTotal,
                'totalAdRequests' => $requestsTotal,
            ],
        ];
    }

    // ── Column definitions ─────────────────────────────────────────────────────

    private static function revenueHeaders(): array
    {
        return array_merge(['Date'], array_values(self::PARTNERS), ['Total']);
    }

    private static function impressionHeaders(): array
    {
        return array_merge(['Date'], array_values(self::PARTNERS), ['Impressions Sold', 'Total Ad Requests']);
    }

    /** Revenue rows + trailing totals row. */
    private static function revenueRows(array $data): array
    {
        $rows = [];
        foreach ($data['days'] as $d) {
            $row = [$d['date']];
            foreach (self::PARTNERS as $key => $label) $row[] = $d['revenue'][$key];
            $row[] = $d['total'];
            $rows[] = $row;
        }
        $tot = ['Total'];
        foreach (self::PARTNERS as $key => $label) $tot[] = $data['totals']['revenue'][$key];
        $tot[] = $data['totals']['total'];
        $rows[] = $tot;

        return $rows;
    }

    /** Impression rows + trailing totals row. */
    private static function impressionRows(array $data): array
    {
        $rows = [];
        foreach ($data['days'] as $d) {
            $row = [$d['date']];
            foreach (self::PARTNERS as $key => $label) $row[] = $d['impressions'][$key];
            $row[] = $d['impressionsSold'];
            $row[] = $d['totalAdRequests'];
            $rows[] = $row;
        }
        $tot = ['Total'];
        foreach (self::PARTNERS as $key => $label) $tot[] = $data['totals']['impressions'][$key];
        $tot[] = $data['totals']['impressionsSold'];
        $tot[] = $data['totals']['totalAdRequests'];
        $rows[] = $tot;

        return $rows;
    }

    // ── Formats ──────────────────────────────────────────────────────────────

    public static function csv(array $data): string
    {
        $fh = fopen('php://temp', 'r+');
        $put = fn ($row) => fputcsv($fh, array_map(fn ($v) => $v === null ? '' : $v, $row), ',', '"', '');

        $put(['Revenues — ' . $data['siteName']]);
        $put(self::revenueHeaders());
        foreach (self::revenueRows($data) as $r) $put($r);
        $put([]); // blank separator line
        $put(['Impressions — ' . $data['siteName']]);
        $put(self::impressionHeaders());
        foreach (self::impressionRows($data) as $r) $put($r);

        rewind($fh);

        return stream_get_contents($fh);
    }

    public static function json(array $data): string
    {
        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    public static function xlsx(array $data): string
    {
        $ss = new Spreadsheet();
        self::fillSheet($ss->getActiveSheet(), 'Impressions', self::impressionHeaders(), self::impressionRows($data), '#,##0');
        self::fillSheet($ss->createSheet(), 'Revenues', self::revenueHeaders(), self::revenueRows($data), '#,##0.00');
        $ss->setActiveSheetIndex(0);

        ob_start();
        (new Xlsx($ss))->save('php://output');
        $bytes = ob_get_clean();
        $ss->disconnectWorksheets();

        return $bytes;
    }

    /** Render one section into a worksheet: red header, numeric format, bold totals row. */
    private static function fillSheet($sheet, string $title, array $headers, array $rows, string $numberFormat): void
    {
        $sheet->setTitle($title);
        $sheet->setShowGridlines(false);

        foreach ($headers as $i => $h) {
            $sheet->setCellValue([$i + 1, 1], $h);
        }
        $lastCol = count($headers);
        $lastColLetter = Coordinate::stringFromColumnIndex($lastCol);
        $sheet->getStyle("A1:{$lastColLetter}1")->getFont()->setBold(true);

        $r = 2;
        foreach ($rows as $row) {
            foreach ($row as $c => $val) {
                if ($val !== null && $val !== '') $sheet->setCellValue([$c + 1, $r], $val);
            }
            $r++;
        }
        $lastRow = $r - 1;

        // Numeric columns (everything but the Date column): right-align the header
        // and the values together, and apply the number format.
        for ($col = 2; $col <= $lastCol; $col++) {
            $letter = Coordinate::stringFromColumnIndex($col);
            $sheet->getStyle("{$letter}1:{$letter}{$lastRow}")
                ->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("{$letter}2:{$letter}{$lastRow}")->getNumberFormat()->setFormatCode($numberFormat);
        }
        $sheet->getStyle("A{$lastRow}:{$lastColLetter}{$lastRow}")->getFont()->setBold(true);
        for ($col = 1; $col <= $lastCol; $col++) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($col))->setAutoSize(true);
        }
    }
}
