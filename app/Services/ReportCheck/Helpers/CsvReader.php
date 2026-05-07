<?php

namespace App\Services\ReportCheck\Helpers;

use App\Services\ReportCheck\ParseException;

class CsvReader
{
    /**
     * Yield each data row as an associative array keyed by the trimmed
     * header values from row `$headerRow` (1-indexed, after BOM strip
     * and optional `#`-comment line removal).
     *
     * @return iterable<int,array<string,?string>>
     */
    public static function rows(string $path, int $headerRow = 1, bool $stripHashComments = false): iterable
    {
        if (!is_readable($path)) {
            throw new ParseException("CSV not readable: {$path}");
        }
        $lines = file($path, FILE_IGNORE_NEW_LINES);
        if ($lines === false) {
            throw new ParseException("CSV read failed: {$path}");
        }

        // Strip UTF-8 BOM if present on the first line.
        if (isset($lines[0])) {
            $lines[0] = preg_replace('/^\xEF\xBB\xBF/', '', $lines[0]);
        }

        if ($stripHashComments) {
            $lines = array_values(array_filter($lines, fn($l) => !str_starts_with($l, '#')));
        }

        if (!isset($lines[$headerRow - 1])) {
            throw new ParseException("CSV {$path} has no header at row {$headerRow}");
        }

        $header = array_map('trim', str_getcsv($lines[$headerRow - 1], ',', '"', '\\'));

        for ($i = $headerRow; $i < count($lines); $i++) {
            if ($lines[$i] === '') continue;
            $cells = str_getcsv($lines[$i], ',', '"', '\\');
            $assoc = [];
            foreach ($header as $idx => $name) {
                if ($name === '') continue;
                $assoc[$name] = $cells[$idx] ?? null;
            }
            yield $assoc;
        }
    }
}
