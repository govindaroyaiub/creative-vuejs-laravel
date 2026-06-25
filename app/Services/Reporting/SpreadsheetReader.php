<?php

namespace App\Services\Reporting;

use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Reads xlsx/csv files into plain PHP arrays, mirroring how the Express app used
 * SheetJS with { raw: true }: numeric/date cells come back as their underlying
 * values (date cells as Excel serials), text cells as strings. Reporting::parseDate
 * / ::stripNum then coerce per-column, exactly as the JS pipeline did.
 *
 * Parsed sheets are memoised per (path, sheet) for the lifetime of the request,
 * because the processing loop re-reads each partner file once per site.
 */
class SpreadsheetReader
{
    /** @var array<string, array<int, array<int, mixed>>> */
    private static array $matrixCache = [];

    /**
     * Associative rows using the first row as (trimmed) headers — the equivalent
     * of XLSX.utils.sheet_to_json(ws, { defval:'', raw:true }).
     *
     * @return list<array<string, mixed>>
     */
    public static function rows(string $path, int|string|array $sheet = 0): array
    {
        $matrix = self::matrix($path, $sheet);
        if (count($matrix) === 0) return [];

        // Some exports (e.g. Teads) leave blank leading rows and store their real
        // range lower down (A10:…). SheetJS honoured the stored range; PhpSpreadsheet
        // fills from A1, so drop fully-empty leading rows to land on the header.
        $start = 0;
        foreach ($matrix as $i => $row) {
            $hasValue = false;
            foreach ($row as $cell) {
                if (trim((string) $cell) !== '') { $hasValue = true; break; }
            }
            if ($hasValue) { $start = $i; break; }
        }

        $headers = array_map(fn ($h) => trim((string) $h), $matrix[$start]);
        $out = [];
        for ($i = $start + 1; $i < count($matrix); $i++) {
            $row = [];
            foreach ($headers as $c => $h) {
                if ($h === '') continue;
                $row[$h] = $matrix[$i][$c] ?? '';
            }
            $out[] = $row;
        }

        return $out;
    }

    /**
     * 0-indexed 2D array of raw cell values (the equivalent of
     * sheet_to_json(ws, { header:1, raw:true })). Used by header-positional
     * parsers (Adform, Verify).
     *
     * @return array<int, array<int, mixed>>
     */
    public static function matrix(string $path, int|string|array $sheet = 0): array
    {
        $key = $path . '::' . (is_array($sheet) ? json_encode($sheet) : (string) $sheet);
        if (isset(self::$matrixCache[$key])) return self::$matrixCache[$key];

        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);

        $worksheetName = self::resolveSheetName($path, $sheet, $reader);
        if ($worksheetName !== null && method_exists($reader, 'setLoadSheetsOnly')) {
            $reader->setLoadSheetsOnly($worksheetName);
        }

        $spreadsheet = $reader->load($path);
        $ws = $worksheetName !== null
            ? $spreadsheet->getSheetByName($worksheetName)
            : $spreadsheet->getActiveSheet();

        // formatData=false → raw values (date serials stay numeric, like raw:true).
        $matrix = $ws ? $ws->toArray('', false, false, false) : [];
        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);

        // Some sheets store their data starting below column A (e.g. a report whose
        // range is B1:K38). SheetJS honoured the stored range origin; PhpSpreadsheet
        // fills from A1, so drop fully-empty leading columns to realign indices.
        $firstCol = null;
        foreach ($matrix as $row) {
            foreach ($row as $c => $cell) {
                if (trim((string) $cell) !== '') {
                    $firstCol = $firstCol === null ? $c : min($firstCol, $c);
                    break;
                }
            }
        }
        if ($firstCol) {
            $matrix = array_map(fn ($r) => array_values(array_slice($r, $firstCol)), $matrix);
        }

        return self::$matrixCache[$key] = $matrix;
    }

    /** Sheet names of a workbook (xlsx). */
    public static function sheetNames(string $path): array
    {
        return IOFactory::createReaderForFile($path)->listWorksheetNames($path);
    }

    /**
     * Parse a CSV into associative rows, mirroring the Express app's parseCSV:
     * leading "#" comment lines (e.g. GA4 exports) are skipped, the first real
     * line is the header, surrounding quotes are stripped and values trimmed.
     *
     * @return list<array<string, string>>
     */
    public static function csvRows(string $contents): array
    {
        $lines = array_values(array_filter(
            preg_split('/\r?\n/', $contents),
            fn ($l) => trim((string) $l) !== ''
        ));
        if (count($lines) === 0) return [];

        $hi = 0;
        foreach ($lines as $i => $l) {
            if (! str_starts_with($l, '#') && trim($l) !== '') { $hi = $i; break; }
        }

        $strip = fn ($v) => trim(preg_replace('/^"|"$/', '', (string) $v));
        $headers = array_map($strip, str_getcsv($lines[$hi]));

        $out = [];
        for ($i = $hi + 1; $i < count($lines); $i++) {
            $vals = str_getcsv($lines[$i]);
            $row = [];
            foreach ($headers as $c => $h) {
                $row[$h] = $strip($vals[$c] ?? '');
            }
            $out[] = $row;
        }

        return $out;
    }

    /**
     * Resolve a sheet selector to a concrete worksheet name:
     *   int                      → that index
     *   string                   → exact name
     *   ['contains' => 'stat']   → first name containing the substring (ci),
     *                              falling back to ['fallbackIndex' => N]
     */
    private static function resolveSheetName(string $path, int|string|array $sheet, $reader): ?string
    {
        $names = $reader->listWorksheetNames($path);
        if (count($names) === 0) return null;

        if (is_int($sheet)) {
            return $names[$sheet] ?? $names[0];
        }
        if (is_string($sheet)) {
            return in_array($sheet, $names, true) ? $sheet : null;
        }

        // array matcher
        if (isset($sheet['contains'])) {
            $needle = mb_strtolower($sheet['contains']);
            foreach ($names as $n) {
                if (str_contains(mb_strtolower($n), $needle)) return $n;
            }
        }
        $idx = $sheet['fallbackIndex'] ?? 0;

        return $names[$idx] ?? $names[0];
    }
}
