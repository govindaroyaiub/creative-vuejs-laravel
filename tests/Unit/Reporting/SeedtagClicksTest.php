<?php

use App\Services\Reporting\ZipBuilder;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * The SeedTag file in the Download Reports ZIP gets a trailing "Clicks" column
 * (0 on every row). The stored upload itself is left untouched.
 */
function seedtagZipRows(array $rows): array
{
    $dir = sys_get_temp_dir() . '/seedtag-' . uniqid();
    mkdir($dir);
    $ss = new Spreadsheet();
    $ss->getActiveSheet()->setTitle('Daily Analytics')->fromArray($rows, null, 'A1', true);
    (new Xlsx($ss))->save("$dir/SeedTag.xlsx");
    $original = file_get_contents("$dir/SeedTag.xlsx");

    file_put_contents("$dir/out.zip", ZipBuilder::build(['sites' => []], $dir, ['SeedTag.xlsx']));
    $zip = new ZipArchive();
    $zip->open("$dir/out.zip");
    file_put_contents("$dir/from-zip.xlsx", $zip->getFromName('SeedTag.xlsx'));
    $zip->close();

    $sheet = IOFactory::load("$dir/from-zip.xlsx")->getSheet(0);
    $out = $sheet->toArray(null, false, false);
    $untouched = file_get_contents("$dir/SeedTag.xlsx") === $original;

    foreach (array_diff(scandir($dir), ['.', '..']) as $f) unlink("$dir/$f");
    rmdir($dir);

    return [$out, $untouched, $sheet->getTitle()];
}

it('adds a Clicks column of zeros to the SeedTag download', function () {
    [$rows, $untouched, $title] = seedtagZipRows([
        ['Date', 'Publisher', 'Revenue', 'Impressions'],
        ['2026-10-18', 'f1maximaal.nl', 14.31, 39724],
        ['2026-10-19', 'topgear.nl', 0, 0],
    ]);

    expect($title)->toBe('Daily Analytics');
    expect($rows)->toBe([
        ['Date', 'Publisher', 'Revenue', 'Impressions', 'Clicks'],
        ['2026-10-18', 'f1maximaal.nl', 14.31, 39724, 0],
        ['2026-10-19', 'topgear.nl', 0, 0, 0],
    ]);
    expect($untouched)->toBeTrue();
});

it('leaves a SeedTag file that already has Clicks unchanged', function () {
    [$rows] = seedtagZipRows([
        ['Date', 'Revenue', 'Clicks'],
        ['2026-10-18', 14.31, 7],
    ]);

    expect($rows)->toBe([
        ['Date', 'Revenue', 'Clicks'],
        ['2026-10-18', 14.31, 7],
    ]);
});
