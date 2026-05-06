<?php

namespace App\Services;

use App\Models\ReportSource;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use RuntimeException;

class ReportFileParser
{
    /**
     * Parse an uploaded report file using the matched source's config and
     * return a map of `Y-m-d` => float (revenue), one entry per data row.
     *
     * @return array<string, float>
     */
    public function parse(string $path, ReportSource $source): array
    {
        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);

        $sheets = $reader->listWorksheetNames($path);
        if (! in_array($source->sheet_name, $sheets, true)) {
            throw new RuntimeException(sprintf(
                'Sheet "%s" not found in %s. Available: %s',
                $source->sheet_name,
                basename($path),
                implode(', ', $sheets) ?: '(none)',
            ));
        }
        $reader->setLoadSheetsOnly([$source->sheet_name]);

        $spreadsheet = $reader->load($path);
        $sheet = $spreadsheet->getSheetByName($source->sheet_name);

        $rows = $sheet->toArray(null, false, false, false);
        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);

        // header_row is 1-indexed in the source config (matches what users see in Excel).
        $headerIdx = max(0, (int) $source->header_row - 1);
        if (! isset($rows[$headerIdx])) {
            throw new RuntimeException("Header row {$source->header_row} not found in sheet {$source->sheet_name}.");
        }

        $header = array_map(static fn ($v) => is_string($v) ? trim($v) : $v, $rows[$headerIdx]);
        $dateCol = $this->findColumn($header, $source->date_column);
        $revCol = $this->findColumn($header, $source->revenue_column);

        if ($dateCol === null) {
            throw new RuntimeException("Date column \"{$source->date_column}\" not found in header.");
        }
        if ($revCol === null) {
            throw new RuntimeException("Revenue column \"{$source->revenue_column}\" not found in header.");
        }

        $out = [];
        for ($i = $headerIdx + 1, $n = count($rows); $i < $n; $i++) {
            $row = $rows[$i];
            $rawDate = $row[$dateCol] ?? null;
            $rawRev = $row[$revCol] ?? null;

            if ($rawDate === null || $rawDate === '') {
                continue;
            }
            // Skip the trailing "Total" row used by Teads (and any similar marker).
            if (is_string($rawDate) && in_array(strtolower(trim($rawDate)), ['total', 'totals', 'grand total', 'sum'], true)) {
                continue;
            }
            if ($rawRev === null || $rawRev === '') {
                continue;
            }

            $date = $this->normalizeDate($rawDate, $source->date_format);
            if ($date === null) {
                continue;
            }

            $revenue = $this->normalizeNumber($rawRev);
            if ($revenue === null) {
                continue;
            }

            // If a file repeats the same date in multiple rows, sum them.
            $key = $date->format('Y-m-d');
            $out[$key] = ($out[$key] ?? 0.0) + $revenue;
        }

        return $out;
    }

    /**
     * Locate which row index contains a column. Tolerates extra whitespace
     * and is case-insensitive — header strings out of the wild are messy.
     */
    private function findColumn(array $header, string $name): ?int
    {
        $needle = strtolower(trim($name));
        foreach ($header as $idx => $value) {
            if (! is_string($value)) {
                continue;
            }
            if (strtolower(trim($value)) === $needle) {
                return $idx;
            }
        }
        return null;
    }

    private function normalizeDate(mixed $raw, ?string $format): ?CarbonImmutable
    {
        // Excel stores dates as floats (serial). PhpSpreadsheet hands us the
        // serial back when read-only mode is on.
        if (is_numeric($raw)) {
            try {
                $dt = ExcelDate::excelToDateTimeObject((float) $raw);
                return CarbonImmutable::instance($dt)->startOfDay();
            } catch (\Throwable) {
                return null;
            }
        }

        if ($raw instanceof \DateTimeInterface) {
            return CarbonImmutable::instance($raw)->startOfDay();
        }

        if (! is_string($raw)) {
            return null;
        }

        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }

        if ($format) {
            try {
                $parsed = CarbonImmutable::createFromFormat($format, $raw);
                if ($parsed) {
                    return $parsed->startOfDay();
                }
            } catch (\Throwable) {
                // fall through to autodetect
            }
        }

        // Fall back: Carbon's parser handles ISO 8601, "Y-m-d H:i:s", etc.
        try {
            return CarbonImmutable::parse($raw)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    private function normalizeNumber(mixed $raw): ?float
    {
        if (is_numeric($raw)) {
            return (float) $raw;
        }
        if (! is_string($raw)) {
            return null;
        }
        $cleaned = preg_replace('/[^0-9.\-,]/', '', $raw) ?? '';
        if ($cleaned === '' || $cleaned === '-' || $cleaned === '.') {
            return null;
        }
        // Handle European decimal comma (e.g., "1.346,32").
        if (str_contains($cleaned, ',') && ! str_contains($cleaned, '.')) {
            $cleaned = str_replace(',', '.', $cleaned);
        } elseif (str_contains($cleaned, ',') && str_contains($cleaned, '.')) {
            // Both present — assume comma is thousands separator.
            $cleaned = str_replace(',', '', $cleaned);
        }
        if (! is_numeric($cleaned)) {
            return null;
        }
        return (float) $cleaned;
    }

    /**
     * Match an uploaded file against the active sources by filename pattern
     * (case-insensitive substring). Returns the matched source or null.
     */
    public function detectSource(string $filename): ?ReportSource
    {
        $lower = strtolower($filename);
        $candidates = ReportSource::query()
            ->where('active', true)
            ->orderByDesc(\DB::raw('LENGTH(filename_pattern)'))
            ->get();

        foreach ($candidates as $source) {
            if (str_contains($lower, strtolower($source->filename_pattern))) {
                return $source;
            }
        }
        return null;
    }
}
