<?php

use App\Services\Reporting\ZipBuilder;

/**
 * macOS drops .DS_Store into the uploads dir; it must not show up in the
 * Download Reports checklist or the ZIP.
 */
it('hides dotfiles from the download list and the ZIP', function () {
    $dir = sys_get_temp_dir() . '/zipbuilder-' . uniqid();
    mkdir($dir);
    file_put_contents("$dir/.DS_Store", 'junk');
    file_put_contents("$dir/._Teads.xlsx", 'junk');
    file_put_contents("$dir/Teads.xlsx", 'data');

    expect(ZipBuilder::availableFiles($dir))->toBe(['Teads.xlsx']);

    $zipPath = "$dir/out.zip";
    file_put_contents($zipPath, ZipBuilder::build(['sites' => []], $dir));
    $zip = new ZipArchive();
    $zip->open($zipPath);
    $names = [];
    for ($i = 0; $i < $zip->numFiles; $i++) $names[] = $zip->getNameIndex($i);
    $zip->close();

    expect($names)->toBe(['Teads.xlsx']);

    foreach (array_diff(scandir($dir), ['.', '..']) as $f) unlink("$dir/$f");
    @rmdir($dir);
});

it('regenerates Adhese jfk.csv from the store instead of shipping the stale disk copy', function () {
    $dir = sys_get_temp_dir() . '/zipbuilder-' . uniqid();
    mkdir($dir);
    file_put_contents("$dir/Adhese jfk.csv", "date,site,market.name,Paid Revenue\n1-Aug-26,JFK.men,,1.62");

    $store = ['sites' => ['jfk' => ['days' => [
        '2026-10-01' => ['dateKey' => '2026-10-01', 'revenue' => ['adhese' => 0.5], 'impressions' => ['adhese' => 223]],
    ]]]];
    $zipPath = "$dir/out.zip";
    file_put_contents($zipPath, ZipBuilder::build($store, $dir, ['Adhese jfk.csv'], '2026-10-01', '2026-10-31'));
    $zip = new ZipArchive();
    $zip->open($zipPath);
    $csv = $zip->getFromName('Adhese jfk.csv');
    $zip->close();

    expect($csv)->toBe("date,site,market.name,Paid Revenue,Impressions\n1-Oct-26,JFK.men,,0.5,223");

    foreach (array_diff(scandir($dir), ['.', '..']) as $f) unlink("$dir/$f");
    @rmdir($dir);
});
