<?php

use App\Services\Reporting\Extractors;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * The Adhese platform sometimes exports XLSX instead of CSV. Extractors::adhese
 * now takes a path and reads either format via SpreadsheetReader. These tests
 * prove both formats parse to the same result.
 */
function writeAdheseXlsx(array $rows): string
{
    $ss = new Spreadsheet();
    $ss->getActiveSheet()->fromArray(
        array_merge([['date', 'site', 'Paid Revenue']], $rows),
        null,
        'A1',
    );
    $tmp = tempnam(sys_get_temp_dir(), 'adhese_') . '.xlsx';
    (new Xlsx($ss))->save($tmp);

    return $tmp;
}

it('parses an Adhese report from an XLSX file', function () {
    $path = writeAdheseXlsx([
        ['01 Jun 2026', 'f1maximaal.nl', 12.34],
        ['02 Jun 2026', 'f1maximaal.nl', 56.78],
    ]);

    $rows = Extractors::adhese($path, 'Adhese f1.xlsx');
    @unlink($path);

    expect($rows)->toHaveCount(2);
    expect($rows[0]['site'])->toBe('f1maximaal.nl');
    expect(round($rows[0]['revenue'], 2))->toBe(12.34);
    expect(round($rows[1]['revenue'], 2))->toBe(56.78);
});

it('falls back to the site from the filename when no site column value', function () {
    // Site column present but blank → should fall back to TopGear via the filename.
    $path = writeAdheseXlsx([['03 Jun 2026', '', 9.99]]);

    $rows = Extractors::adhese($path, 'Adhese tg.xlsx');
    @unlink($path);

    expect($rows)->toHaveCount(1);
    expect($rows[0]['site'])->toBe('topgear.nl');
    expect(round($rows[0]['revenue'], 2))->toBe(9.99);
});

it('still parses the CSV Adhese fixtures (same code path)', function () {
    $dir = __DIR__ . '/../../Fixtures/Reporting/uploads';
    $rows = Extractors::adhese("$dir/Adhese f1.csv", 'Adhese f1.csv');

    expect($rows)->not->toBeEmpty();
    expect($rows[0])->toHaveKeys(['date', 'site', 'revenue']);
});
