<?php

namespace App\Services\Reporting;

use RuntimeException;
use ZipArchive;

/**
 * Builds the "F1Maximaal Reports.zip" download, ported from server.js
 * /download/zip. Analytics.csv and the Adhese CSVs are regenerated from the
 * store (so a date range can be applied); other files are added from disk.
 * planetnine-report-* files are always excluded.
 */
class ZipBuilder
{
    public static function build(array $store, string $uploadsDir, ?array $requested = null, ?string $from = null, ?string $to = null): string
    {
        $exclude = fn (string $name) => str_starts_with(mb_strtolower($name), 'planetnine-report-');

        $all = array_values(array_filter(
            is_dir($uploadsDir) ? array_diff(scandir($uploadsDir), ['.', '..']) : [],
            fn ($f) => is_file($uploadsDir . '/' . $f) && ! $exclude($f),
        ));
        if (count($all) === 0) throw new RuntimeException('No files to download yet');

        $files = $requested !== null ? array_values(array_filter($all, fn ($f) => in_array($f, $requested, true))) : $all;
        if (count($files) === 0) throw new RuntimeException('No matching files selected');

        $tmp = tempnam(sys_get_temp_dir(), 'rpt') . '.zip';
        $zip = new ZipArchive();
        $zip->open($tmp, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        foreach ($files as $f) {
            $generated = match ($f) {
                'Analytics.csv' => CsvGenerator::analytics($store, $from, $to),
                'Adhese f1.csv' => CsvGenerator::adhese($store, 'f1maximaal', $from, $to),
                'Adhese tg.csv' => CsvGenerator::adhese($store, 'topgear', $from, $to),
                'Adhese fl.csv' => CsvGenerator::adhese($store, 'festileaks', $from, $to),
                default => null,
            };
            if ($generated !== null) {
                $zip->addFromString($f, $generated);
            } else {
                $zip->addFile($uploadsDir . '/' . $f, $f);
            }
        }

        $zip->close();
        $buf = file_get_contents($tmp);
        @unlink($tmp);

        return $buf;
    }

    /** Files currently available for download (for the ZIP modal checklist). */
    public static function availableFiles(string $uploadsDir): array
    {
        if (! is_dir($uploadsDir)) return [];
        $exclude = fn (string $name) => str_starts_with(mb_strtolower($name), 'planetnine-report-');
        $files = array_values(array_filter(
            array_diff(scandir($uploadsDir), ['.', '..']),
            fn ($f) => is_file($uploadsDir . '/' . $f) && ! $exclude($f),
        ));
        sort($files);

        return $files;
    }
}
