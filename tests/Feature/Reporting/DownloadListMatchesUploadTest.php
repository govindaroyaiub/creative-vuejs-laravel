<?php

use App\Services\Reporting\ReportProcessor;
use App\Services\Reporting\ZipBuilder;
use Carbon\CarbonImmutable;

/**
 * The Download Reports list shows exactly the files of the latest upload:
 * generated Adhese/Analytics CSVs only for sites whose file was uploaded, and
 * nothing left over from an earlier upload.
 */
it('lists only the files from the latest upload', function () {
    $fixtures = __DIR__ . '/../../Fixtures/Reporting/uploads';
    $dir = storage_path('framework/testing/download-list-uploads');
    if (! is_dir($dir)) mkdir($dir, 0775, true);
    $today = CarbonImmutable::create(2026, 6, 25);
    // Fixtures are stored under canonical names; uploads arrive with the partners'
    // original filenames, which is what file detection keys off.
    $original = [
        'Analytics.csv' => 'Pages_and_screens_Content_group.csv',
        'SeedTag.xlsx' => 'revenue-export-1782397575032.xlsx',
        'Teads.xlsx' => 'report_finance_06-25-26-202711.xlsx',
    ];
    $run = fn (array $names) => ReportProcessor::process(
        array_map(fn ($n) => ['name' => $original[$n] ?? $n, 'path' => "$fixtures/$n"], $names), $dir, $today,
    );

    // First upload includes TopGear's Adhese file.
    $run(['Analytics.csv', 'Adhese f1.csv', 'Adhese tg.csv', 'SeedTag.xlsx']);
    expect(ZipBuilder::availableFiles($dir))
        ->toBe(['Adhese f1.csv', 'Adhese tg.csv', 'Analytics f1.csv', 'SeedTag.xlsx']);

    // Second upload has no TopGear file and no SeedTag: neither shows up anymore,
    // even though TopGear data is still stored from the first run.
    $run(['Analytics.csv', 'Adhese f1.csv', 'Teads.xlsx']);
    expect(ZipBuilder::availableFiles($dir))
        ->toBe(['Adhese f1.csv', 'Analytics f1.csv', 'Teads.xlsx']);
});
