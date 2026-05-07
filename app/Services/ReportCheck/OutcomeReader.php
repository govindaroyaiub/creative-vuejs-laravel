<?php

namespace App\Services\ReportCheck;

use App\Services\ReportCheck\Helpers\Money;
use DateTime;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Read a produced "Revenue Report" workbook into a flat list of typed
 * cells the validator can iterate over.
 *
 * Output shape (also persisted as `report_checks.outcome_snapshot`):
 * [
 *   'period_label' => 'MAY 2026',
 *   'cells' => [
 *     [
 *       'sheet'    => 'Summary',
 *       'cell_ref' => 'D6',
 *       'role'     => 'summary.value',
 *       'attrs'    => ['block'=>'display','date'=>'2026-05-01','partner'=>'Adform'],
 *       'value'    => 9.51,
 *     ], ...
 *   ],
 *   'errors' => []
 * ]
 *
 * Each cell's `role` is the discriminator the validator switches on:
 *   summary.value           — date × partner cell in a section block
 *   summary.row_total       — Total column on a section row
 *   summary.col_total       — TOTAL row × partner cell
 *   summary.section_total   — TOTAL × Total intersection (block grand)
 *   summary.daily_total_row — date × format cell in DAILY TOTALS
 *   summary.daily_grand     — TOTAL × Total in DAILY TOTALS
 *   summary.section_totals  — Format × partner in SECTION TOTALS
 *   summary.section_totals_row_total — Format × Total
 *   summary.grand_total     — single GRAND TOTAL cell
 *   demand_partners.value   — date × partner
 *   demand_partners.total   — date × Total column
 *   trend.metric            — date × {pageviews,revenue,rpm,ad_requests,ad_req_per_pv,impressions_sold,sold_trend}
 *   section_sheet.value     — date × revenue on Sticky/Inarticle/Display/Interscroller sheet
 */
class OutcomeReader
{
    private const SECTION_BLOCK_LABELS = [
        'GENERAL DISPLAY'   => SectionClassifier::DISPLAY,
        'STICKY DISPLAY'    => SectionClassifier::STICKY,
        'INARTICLE DISPLAY' => SectionClassifier::INARTICLE,
    ];

    private const PARTNERS = ['Adform', 'GAM', 'Ogury', 'Seedtag', 'Showheroes', 'Teads', 'Preferred Deals', 'Outbrain', 'Adhese'];

    public function read(string $path): array
    {
        try {
            $ss = IOFactory::load($path);
        } catch (\Throwable $e) {
            throw new ParseException("Could not open outcome XLSX '{$path}': " . $e->getMessage(), 0, $e);
        }

        $cells = [];
        $errors = [];
        $periodLabel = null;

        // --- Summary sheet ---
        $summary = $ss->getSheetByName('Summary');
        if ($summary === null) {
            $errors[] = "Sheet 'Summary' not found";
        } else {
            $rows = $summary->toArray(null, true, true, false);
            $periodLabel = $this->extractPeriodLabel($rows);
            $this->extractSummary($rows, $cells, $errors);
        }

        // --- Demand Partners ---
        $dp = $ss->getSheetByName('Demand Partners');
        if ($dp === null) {
            $errors[] = "Sheet 'Demand Partners' not found";
        } else {
            $this->extractDemandPartners($dp->toArray(null, true, true, false), $cells, $errors);
        }

        // --- Trend ---
        $trend = $ss->getSheetByName('Trend');
        if ($trend === null) {
            $errors[] = "Sheet 'Trend' not found";
        } else {
            $this->extractTrend($trend->toArray(null, true, true, false), $cells, $errors);
        }

        // --- Per-section sheets (Sticky / Inarticle / Display / Interscroller) ---
        foreach ([
            'Sticky'        => SectionClassifier::STICKY,
            'Inarticle'     => SectionClassifier::INARTICLE,
            'Display'       => SectionClassifier::DISPLAY,
            'Interscroller' => SectionClassifier::INTERSCROLLER,
        ] as $sheetName => $section) {
            $ws = $ss->getSheetByName($sheetName);
            if ($ws === null) {
                $errors[] = "Sheet '{$sheetName}' not found";
                continue;
            }
            $this->extractSectionSheet($ws->toArray(null, true, true, false), $sheetName, $section, $cells, $errors);
        }

        return [
            'period_label' => $periodLabel,
            'cells'        => $cells,
            'errors'       => $errors,
        ];
    }

    private function extractPeriodLabel(array $rows): ?string
    {
        // Title sits in row 3 col D in the sample; scan first 5 rows for any cell containing "REVENUE REPORT".
        for ($r = 0; $r < min(5, count($rows)); $r++) {
            foreach ($rows[$r] ?? [] as $v) {
                if (is_string($v) && stripos($v, 'REVENUE REPORT') !== false) return trim($v);
            }
        }
        return null;
    }

    /** @param array<int,array> $rows */
    private function extractSummary(array $rows, array &$cells, array &$errors): void
    {
        // Map labels in column B (idx 1) to row indexes.
        $labelRows = [];
        foreach ($rows as $i => $row) {
            $b = $row[1] ?? null;
            if (!is_string($b)) continue;
            $key = strtoupper(trim($b));
            if (in_array($key, ['GENERAL DISPLAY', 'STICKY DISPLAY', 'INARTICLE DISPLAY', 'DAILY TOTALS', 'SECTION TOTALS', 'GRAND TOTAL'], true)) {
                $labelRows[$key] = $i;
            }
        }

        // Three per-section blocks.
        foreach (self::SECTION_BLOCK_LABELS as $label => $section) {
            if (!isset($labelRows[$label])) {
                $errors[] = "Summary block '{$label}' not found";
                continue;
            }
            $this->extractSummarySectionBlock($rows, $labelRows[$label], $section, $cells, $errors);
        }

        // DAILY TOTALS.
        if (isset($labelRows['DAILY TOTALS'])) {
            $this->extractDailyTotalsBlock($rows, $labelRows['DAILY TOTALS'], $cells, $errors);
        } else {
            $errors[] = "Summary block 'DAILY TOTALS' not found";
        }

        // SECTION TOTALS.
        if (isset($labelRows['SECTION TOTALS'])) {
            $this->extractSectionTotalsBlock($rows, $labelRows['SECTION TOTALS'], $cells, $errors);
        } else {
            $errors[] = "Summary block 'SECTION TOTALS' not found";
        }

        // GRAND TOTAL.
        if (isset($labelRows['GRAND TOTAL'])) {
            $r = $labelRows['GRAND TOTAL'];
            // GRAND TOTAL value is in col D (idx 3) in the sample; sweep cols D-Z for the first numeric.
            $value = $this->firstNumericInRow($rows[$r] ?? [], 2);
            $col = $this->firstNumericColumn($rows[$r] ?? [], 2);
            if ($value !== null && $col !== null) {
                $cells[] = [
                    'sheet'    => 'Summary',
                    'cell_ref' => $this->coord($col, $r),
                    'role'     => 'summary.grand_total',
                    'attrs'    => [],
                    'value'    => $value,
                ];
            } else {
                $errors[] = "GRAND TOTAL cell not found in row " . ($r + 1);
            }
        }
    }

    /**
     * Extract a section block: GENERAL/STICKY/INARTICLE DISPLAY.
     * Layout (relative to the label row):
     *   +0: label in col B
     *   +1: header  → Date | Total | Adform | GAM | Ogury | Seedtag | Showheroes | Teads | Preferred Deals | Outbrain | Adhese
     *   +2..+N: per-day rows
     *   +N+1: TOTAL row
     */
    private function extractSummarySectionBlock(array $rows, int $labelRowIdx, string $section, array &$cells, array &$errors): void
    {
        $headerIdx = $labelRowIdx + 1;
        $headerRow = $rows[$headerIdx] ?? null;
        if (!$headerRow || strcasecmp(trim((string)($headerRow[1] ?? '')), 'Date') !== 0) {
            $errors[] = "Section '{$section}' header row missing or malformed at row " . ($headerIdx + 1);
            return;
        }
        $partnerCols = $this->mapPartnerColumns($headerRow);
        $totalCol = $this->findColumn($headerRow, 'Total');

        // Walk data rows until TOTAL.
        for ($i = $headerIdx + 1; $i < count($rows); $i++) {
            $row = $rows[$i];
            $label = is_string($row[1] ?? null) ? trim($row[1]) : '';
            if ($label === '') break; // blank — block ended without finding TOTAL

            if (strcasecmp($label, 'TOTAL') === 0) {
                // Column totals (per partner) + section grand total (Total col).
                foreach ($partnerCols as $partner => $col) {
                    $cells[] = [
                        'sheet'    => 'Summary',
                        'cell_ref' => $this->coord($col, $i),
                        'role'     => 'summary.col_total',
                        'attrs'    => ['block' => $section, 'partner' => $partner],
                        'value'    => Money::parse($row[$col] ?? null),
                    ];
                }
                if ($totalCol !== null) {
                    $cells[] = [
                        'sheet'    => 'Summary',
                        'cell_ref' => $this->coord($totalCol, $i),
                        'role'     => 'summary.section_total',
                        'attrs'    => ['block' => $section],
                        'value'    => Money::parse($row[$totalCol] ?? null),
                    ];
                }
                return;
            }

            $date = $this->parseDate($label);
            if ($date === null) continue;

            // Per-partner cells.
            foreach ($partnerCols as $partner => $col) {
                $cells[] = [
                    'sheet'    => 'Summary',
                    'cell_ref' => $this->coord($col, $i),
                    'role'     => 'summary.value',
                    'attrs'    => ['block' => $section, 'date' => $date, 'partner' => $partner],
                    'value'    => Money::parse($row[$col] ?? null),
                ];
            }
            // Row total (Total column).
            if ($totalCol !== null) {
                $cells[] = [
                    'sheet'    => 'Summary',
                    'cell_ref' => $this->coord($totalCol, $i),
                    'role'     => 'summary.row_total',
                    'attrs'    => ['block' => $section, 'date' => $date],
                    'value'    => Money::parse($row[$totalCol] ?? null),
                ];
            }
        }

        $errors[] = "Section '{$section}' TOTAL row not found";
    }

    /**
     * DAILY TOTALS block:
     *   header → Date | Total | InArticle | Display | Sticky | Interscroller
     */
    private function extractDailyTotalsBlock(array $rows, int $labelRowIdx, array &$cells, array &$errors): void
    {
        $headerIdx = $labelRowIdx + 1;
        $headerRow = $rows[$headerIdx] ?? null;
        if (!$headerRow) { $errors[] = "DAILY TOTALS header missing"; return; }

        $formatCols = []; // section_key => col idx
        foreach ($headerRow as $idx => $h) {
            if (!is_string($h)) continue;
            $name = strtolower(trim($h));
            $section = match ($name) {
                'inarticle' => SectionClassifier::INARTICLE,
                'display'   => SectionClassifier::DISPLAY,
                'sticky'    => SectionClassifier::STICKY,
                'interscroller' => SectionClassifier::INTERSCROLLER,
                default => null,
            };
            if ($section !== null) $formatCols[$section] = $idx;
        }
        $totalCol = $this->findColumn($headerRow, 'Total');

        for ($i = $headerIdx + 1; $i < count($rows); $i++) {
            $row = $rows[$i];
            $label = is_string($row[1] ?? null) ? trim($row[1]) : '';
            if ($label === '') break;

            if (strcasecmp($label, 'TOTAL') === 0) {
                foreach ($formatCols as $section => $col) {
                    $cells[] = [
                        'sheet'    => 'Summary',
                        'cell_ref' => $this->coord($col, $i),
                        'role'     => 'summary.daily_total_col_total',
                        'attrs'    => ['block' => $section],
                        'value'    => Money::parse($row[$col] ?? null),
                    ];
                }
                if ($totalCol !== null) {
                    $cells[] = [
                        'sheet'    => 'Summary',
                        'cell_ref' => $this->coord($totalCol, $i),
                        'role'     => 'summary.daily_grand',
                        'attrs'    => [],
                        'value'    => Money::parse($row[$totalCol] ?? null),
                    ];
                }
                return;
            }

            $date = $this->parseDate($label);
            if ($date === null) continue;
            foreach ($formatCols as $section => $col) {
                $cells[] = [
                    'sheet'    => 'Summary',
                    'cell_ref' => $this->coord($col, $i),
                    'role'     => 'summary.daily_total_row',
                    'attrs'    => ['date' => $date, 'block' => $section],
                    'value'    => Money::parse($row[$col] ?? null),
                ];
            }
            if ($totalCol !== null) {
                $cells[] = [
                    'sheet'    => 'Summary',
                    'cell_ref' => $this->coord($totalCol, $i),
                    'role'     => 'summary.daily_total_row_total',
                    'attrs'    => ['date' => $date],
                    'value'    => Money::parse($row[$totalCol] ?? null),
                ];
            }
        }
        $errors[] = "DAILY TOTALS TOTAL row not found";
    }

    /**
     * SECTION TOTALS block:
     *   header → Format | Total | Adform | Ogury | GAM | Seedtag | ShowHeroes | Teads | Preferred Deals | Outbrain | Adhese
     *   data: Display, Inarticle, Sticky (one row each)
     */
    private function extractSectionTotalsBlock(array $rows, int $labelRowIdx, array &$cells, array &$errors): void
    {
        $headerIdx = $labelRowIdx + 1;
        $headerRow = $rows[$headerIdx] ?? null;
        if (!$headerRow) { $errors[] = "SECTION TOTALS header missing"; return; }

        $partnerCols = $this->mapPartnerColumns($headerRow);
        $totalCol = $this->findColumn($headerRow, 'Total');

        for ($i = $headerIdx + 1; $i < count($rows); $i++) {
            $row = $rows[$i];
            $label = is_string($row[1] ?? null) ? trim($row[1]) : '';
            if ($label === '') break;

            $section = match (strtolower($label)) {
                'display'       => SectionClassifier::DISPLAY,
                'inarticle'     => SectionClassifier::INARTICLE,
                'sticky'        => SectionClassifier::STICKY,
                'interscroller' => SectionClassifier::INTERSCROLLER,
                default         => null,
            };
            if ($section === null) continue;

            foreach ($partnerCols as $partner => $col) {
                $cells[] = [
                    'sheet'    => 'Summary',
                    'cell_ref' => $this->coord($col, $i),
                    'role'     => 'summary.section_totals',
                    'attrs'    => ['block' => $section, 'partner' => $partner],
                    'value'    => Money::parse($row[$col] ?? null),
                ];
            }
            if ($totalCol !== null) {
                $cells[] = [
                    'sheet'    => 'Summary',
                    'cell_ref' => $this->coord($totalCol, $i),
                    'role'     => 'summary.section_totals_row_total',
                    'attrs'    => ['block' => $section],
                    'value'    => Money::parse($row[$totalCol] ?? null),
                ];
            }
        }
    }

    private function extractDemandPartners(array $rows, array &$cells, array &$errors): void
    {
        // Find the header row: first row whose col B is "Date".
        $headerIdx = null;
        for ($i = 0; $i < count($rows); $i++) {
            if (strcasecmp(trim((string)($rows[$i][1] ?? '')), 'Date') === 0) { $headerIdx = $i; break; }
        }
        if ($headerIdx === null) { $errors[] = "Demand Partners header not found"; return; }

        $partnerCols = $this->mapPartnerColumns($rows[$headerIdx]);
        $totalCol = $this->findColumn($rows[$headerIdx], 'Total');

        for ($i = $headerIdx + 1; $i < count($rows); $i++) {
            $row = $rows[$i];
            $label = is_string($row[1] ?? null) ? trim($row[1]) : '';
            $date = $this->parseDate($label);
            if ($date === null) continue;

            foreach ($partnerCols as $partner => $col) {
                $cells[] = [
                    'sheet'    => 'Demand Partners',
                    'cell_ref' => $this->coord($col, $i),
                    'role'     => 'demand_partners.value',
                    'attrs'    => ['date' => $date, 'partner' => $partner],
                    'value'    => Money::parse($row[$col] ?? null),
                ];
            }
            if ($totalCol !== null) {
                $cells[] = [
                    'sheet'    => 'Demand Partners',
                    'cell_ref' => $this->coord($totalCol, $i),
                    'role'     => 'demand_partners.total',
                    'attrs'    => ['date' => $date],
                    'value'    => Money::parse($row[$totalCol] ?? null),
                ];
            }
        }
    }

    private function extractTrend(array $rows, array &$cells, array &$errors): void
    {
        // Find the header row (col B = 'Date').
        $headerIdx = null;
        for ($i = 0; $i < count($rows); $i++) {
            if (strcasecmp(trim((string)($rows[$i][1] ?? '')), 'Date') === 0) { $headerIdx = $i; break; }
        }
        if ($headerIdx === null) { $errors[] = "Trend header not found"; return; }

        $metricCols = []; // metric_key => col idx
        foreach ($rows[$headerIdx] as $idx => $h) {
            if (!is_string($h)) continue;
            $name = strtolower(trim($h));
            $metric = match (true) {
                $name === 'pageviews'                                  => 'pageviews',
                $name === 'revenues' || $name === 'revenue'            => 'revenues',
                $name === 'rpm'                                        => 'rpm',
                $name === 'total ad requests'                          => 'ad_requests',
                str_contains($name, 'ad request') && str_contains($name, 'pageview') => 'ad_req_per_pv',
                $name === 'impressions sold'                           => 'impressions_sold',
                $name === 'sold trend'                                 => 'sold_trend',
                default                                                => null,
            };
            if ($metric !== null) $metricCols[$metric] = $idx;
        }

        for ($i = $headerIdx + 1; $i < count($rows); $i++) {
            $row = $rows[$i];
            $label = is_string($row[1] ?? null) ? trim($row[1]) : '';
            $date = $this->parseDate($label);
            if ($date === null) continue;

            foreach ($metricCols as $metric => $col) {
                $raw = $row[$col] ?? null;
                $value = ($metric === 'sold_trend') ? $this->parsePercent($raw) : Money::parse($raw);
                $cells[] = [
                    'sheet'    => 'Trend',
                    'cell_ref' => $this->coord($col, $i),
                    'role'     => 'trend.metric',
                    'attrs'    => ['date' => $date, 'metric' => $metric],
                    'value'    => $value,
                ];
            }
        }
    }

    /**
     * Per-section sheets — flat 3-column table:
     *   Date | Category | Revenue
     */
    private function extractSectionSheet(array $rows, string $sheetName, string $section, array &$cells, array &$errors): void
    {
        if (empty($rows)) return;
        // Header is on row 0 in the sample.
        $header = $rows[0];
        if (!$header || strcasecmp(trim((string)($header[0] ?? '')), 'Date') !== 0) {
            $errors[] = "Sheet '{$sheetName}' header missing";
            return;
        }
        $revenueCol = $this->findColumn($header, 'Revenue', 0);
        if ($revenueCol === null) { $errors[] = "Sheet '{$sheetName}' has no Revenue column"; return; }

        for ($i = 1; $i < count($rows); $i++) {
            $row = $rows[$i];
            $label = is_string($row[0] ?? null) ? trim($row[0]) : '';
            $date = $this->parseDate($label);
            if ($date === null) continue;
            $cells[] = [
                'sheet'    => $sheetName,
                'cell_ref' => $this->coord($revenueCol, $i),
                'role'     => 'section_sheet.value',
                'attrs'    => ['date' => $date, 'block' => $section],
                'value'    => Money::parse($row[$revenueCol] ?? null),
            ];
        }
    }

    // ---- Helpers ----

    /** @return array<string,int> partner display name => column index */
    private function mapPartnerColumns(array $headerRow): array
    {
        $out = [];
        foreach ($headerRow as $idx => $h) {
            if (!is_string($h)) continue;
            $norm = strtolower(trim($h));
            foreach (self::PARTNERS as $p) {
                if (strtolower($p) === $norm) { $out[$p] = $idx; break; }
            }
        }
        return $out;
    }

    private function findColumn(array $headerRow, string $name, int $startIdx = 0): ?int
    {
        foreach ($headerRow as $idx => $h) {
            if ($idx < $startIdx) continue;
            if (is_string($h) && strcasecmp(trim($h), $name) === 0) return $idx;
        }
        return null;
    }

    private function firstNumericInRow(array $row, int $startCol = 0): ?float
    {
        foreach ($row as $idx => $v) {
            if ($idx < $startCol) continue;
            if ($v === null || $v === '' || $v === '-') continue;
            $f = Money::parse($v);
            if ($f != 0.0 || (is_numeric($v) && (float)$v === 0.0)) return $f;
        }
        return null;
    }

    private function firstNumericColumn(array $row, int $startCol = 0): ?int
    {
        foreach ($row as $idx => $v) {
            if ($idx < $startCol) continue;
            if ($v === null || $v === '' || $v === '-') continue;
            if (Money::parse($v) != 0.0 || (is_numeric($v) && (float)$v === 0.0)) return $idx;
        }
        return null;
    }

    private function parseDate(string $label): ?string
    {
        if ($label === '' || strcasecmp($label, 'TOTAL') === 0) return null;
        $d = DateTime::createFromFormat('d M Y', $label) ?: DateTime::createFromFormat('j M Y', $label);
        return $d ? $d->format('Y-m-d') : null;
    }

    private function parsePercent(mixed $v): float
    {
        if ($v === null || $v === '') return 0.0;
        if (is_numeric($v)) return (float)$v; // already a fraction (0.57)
        $s = str_replace(['%', ' '], '', (string)$v);
        // Excel often emits percents as "57%" — convert to fraction.
        if (str_contains((string)$v, '%')) return ((float)$s) / 100.0;
        return (float)$s;
    }

    /** Convert (col_idx_0, row_idx_0) → "B6" style cell reference. */
    private function coord(int $col0, int $row0): string
    {
        return Coordinate::stringFromColumnIndex($col0 + 1) . ($row0 + 1);
    }
}
