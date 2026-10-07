<?php

use App\Models\ReportSetting;
use App\Services\Reporting\CsvGenerator;
use App\Services\Reporting\ReportProcessor;
use App\Services\Reporting\ReportStore;
use Carbon\CarbonImmutable;

/**
 * market.name in the downloaded Adhese CSV is whatever the uploaded Adhese file
 * said when it was processed — not a hardcoded per-site value.
 */
it('takes market.name from the processed Adhese file', function () {
    $dir = storage_path('framework/testing/adhese-market-test');
    if (! is_dir($dir)) mkdir($dir, 0775, true);
    $path = "$dir/Adhese jfk.csv";
    file_put_contents($path, "date,market.name,Paid Revenue\n\"Oct 1, 2026\",SOME-market,0.5\n");

    ReportProcessor::process(
        [['name' => 'Adhese jfk.csv', 'path' => $path]],
        storage_path('framework/testing/adhese-market-uploads'),
        CarbonImmutable::create(2026, 10, 5),
    );
    unlink($path);

    expect(ReportSetting::get('adhese_markets'))->toBe(['jfk' => 'SOME-market']);

    $csv = CsvGenerator::adhese(ReportStore::load(), 'jfk');
    expect(explode("\n", $csv)[1])->toBe('1-Oct-26,JFK.men,SOME-market,0.5,0');
});
