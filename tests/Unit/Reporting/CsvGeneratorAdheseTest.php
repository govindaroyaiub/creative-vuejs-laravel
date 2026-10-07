<?php

use App\Services\Reporting\CsvGenerator;
use App\Services\Reporting\Extractors;

/**
 * The Adhese CSV in the Download Reports ZIP carries the hand-entered Adhese
 * impressions next to the revenue, and the market.name from the uploaded file.
 */
function adheseStore(array $days, string $siteId = 'f1maximaal', ?string $market = 'DALE-igmn'): array
{
    $map = [];
    foreach ($days as $d) $map[$d['dateKey']] = $d;

    return [
        'sites' => [$siteId => ['days' => $map]],
        'config' => ['adheseMarkets' => $market === null ? [] : [$siteId => $market]],
    ];
}

it('leaves market.name blank when no Adhese file has been processed for the site', function () {
    $csv = CsvGenerator::adhese(adheseStore([
        ['dateKey' => '2026-10-01', 'revenue' => ['adhese' => 0.5], 'impressions' => ['adhese' => 223]],
    ], 'jfk', null), 'jfk');

    expect(explode("\n", $csv)[1])->toBe('1-Oct-26,JFK.men,,0.5,223');
});

it('keeps the Festileaks file revenue-only', function () {
    $csv = CsvGenerator::adhese(adheseStore([
        ['dateKey' => '2026-10-01', 'revenue' => ['adhese' => 5], 'impressions' => ['adhese' => 99]],
    ], 'festileaks'), 'festileaks');

    $lines = explode("\n", $csv);
    expect($lines[0])->toBe('date,site,market.name,Paid Revenue');
    expect($lines[1])->toBe('1-Oct-26,Festileaks.com,DALE-igmn,5');
});

it('includes impressions in the JFK file', function () {
    $csv = CsvGenerator::adhese(adheseStore([
        ['dateKey' => '2026-10-01', 'revenue' => ['adhese' => 0.5], 'impressions' => ['adhese' => 223]],
    ], 'jfk'), 'jfk');

    expect(explode("\n", $csv)[1])->toBe('1-Oct-26,JFK.men,DALE-igmn,0.5,223');
});

it('adds an Impressions column with the entered Adhese impressions', function () {
    $csv = CsvGenerator::adhese(adheseStore([
        ['dateKey' => '2026-10-01', 'revenue' => ['adhese' => 42.5], 'impressions' => ['adhese' => 17567]],
    ]));

    $lines = explode("\n", $csv);
    expect($lines[0])->toBe('date,site,market.name,Paid Revenue,Impressions');
    expect($lines[1])->toBe('1-Oct-26,F1Maximaal.nl,DALE-igmn,42.5,17567');
});

it('writes 0 Impressions when none were entered', function () {
    $csv = CsvGenerator::adhese(adheseStore([
        ['dateKey' => '2026-10-02', 'revenue' => ['adhese' => 10], 'impressions' => ['adhese' => null]],
        ['dateKey' => '2026-10-03', 'revenue' => ['adhese' => 11]],
    ]));

    $lines = explode("\n", $csv);
    expect($lines[1])->toBe('2-Oct-26,F1Maximaal.nl,DALE-igmn,10,0');
    expect($lines[2])->toBe('3-Oct-26,F1Maximaal.nl,DALE-igmn,11,0');
});

it('still re-uploads cleanly with the extra column', function () {
    $csv = CsvGenerator::adhese(adheseStore([
        ['dateKey' => '2026-10-01', 'revenue' => ['adhese' => 42.5], 'impressions' => ['adhese' => 17567]],
    ]));
    $path = tempnam(sys_get_temp_dir(), 'adh') . '.csv';
    file_put_contents($path, $csv);

    $rows = Extractors::adhese($path, 'Adhese f1.csv');
    @unlink($path);

    expect($rows)->toHaveCount(1);
    expect($rows[0]['site'])->toBe('f1maximaal.nl');
    expect($rows[0]['revenue'])->toBe(42.5);
});
