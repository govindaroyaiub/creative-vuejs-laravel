<?php

namespace App\Services\ReportCheck\Helpers;

use App\Services\ReportCheck\ParseException;
use PhpOffice\PhpSpreadsheet\IOFactory;

class XlsxReader
{
    /**
     * Yield each data row of `$sheet` as an associative array keyed by
     * the trimmed header values from row `$headerRow` (1-indexed).
     * Rows that are entirely null/empty are skipped.
     *
     * @return iterable<int,array<string,mixed>>
     */
    public static function rows(string $path, string $sheet, int $headerRow = 1): iterable
    {
        try {
            $ss = IOFactory::load($path);
        } catch (\Throwable $e) {
            throw new ParseException("Could not open XLSX '{$path}': " . $e->getMessage(), 0, $e);
        }

        $ws = $ss->getSheetByName($sheet);
        if ($ws === null) {
            $available = implode(', ', $ss->getSheetNames());
            throw new ParseException("Sheet '{$sheet}' not found in '{$path}' (available: {$available})");
        }

        $rows = $ws->toArray(null, true, true, false);
        $header = [];
        foreach ($rows[$headerRow - 1] ?? [] as $idx => $col) {
            if ($col === null || $col === '') continue;
            $header[$idx] = is_string($col) ? trim($col) : $col;
        }
        if (empty($header)) {
            throw new ParseException("Header row {$headerRow} of sheet '{$sheet}' is empty");
        }

        for ($i = $headerRow; $i < count($rows); $i++) {
            $row = $rows[$i];
            if (self::isBlankRow($row)) continue;
            $assoc = [];
            foreach ($header as $idx => $name) {
                $assoc[$name] = $row[$idx] ?? null;
            }
            yield $assoc;
        }
    }

    private static function isBlankRow(array $row): bool
    {
        foreach ($row as $v) {
            if ($v !== null && $v !== '') return false;
        }
        return true;
    }
}
