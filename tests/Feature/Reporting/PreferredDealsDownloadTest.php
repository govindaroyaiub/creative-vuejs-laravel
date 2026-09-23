<?php

use App\Services\Reporting\ReportProcessor;
use App\Services\Reporting\Reporting;
use App\Services\Reporting\ZipBuilder;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * GAM's automated Preferred Deals export ("Automated Report - GAM -
 * Preferred_Deals f1max (…)") must be recognised, and the raw file re-saved
 * as PreferredDeals.xlsx so it appears in the Download reports modal like
 * every other partner file.
 */
const PD_NAME = 'Automated Report - GAM - Preferred_Deals f1max (Sep 1, 2026 - Sep 22, 2026).xlsx';

function makePreferredDealsXlsx(string $path): void
{
    $ss = new Spreadsheet();
    $sheet = $ss->getActiveSheet();
    $sheet->fromArray([
        ['Ad unit (all levels)', 'Date', 'Ad server impressions', 'Ad server CPM and CPC revenue', 'Total revenue'],
        ['VM_F1Maximaal » F1M_desktop_article_top_horizontal_alpha', now()->format('Y-m-d'), 100, 0.5, 0.5],
        ['VM_F1Maximaal » F1M_mobile_article_in-content_mixed_repeater', now()->format('Y-m-d'), 200, 1.0, 1.0],
    ]);
    (new Xlsx($ss))->save($path);
    $ss->disconnectWorksheets();
}

it('detects the automated GAM Preferred Deals export by filename', function () {
    expect(Reporting::detectFileType(PD_NAME))->toBe('preferreddeals');
});

it('re-saves the Preferred Deals file for download and keeps it across runs', function () {
    $dir = storage_path('framework/testing/preferred-deals-uploads');
    if (is_dir($dir)) array_map('unlink', glob("$dir/*") ?: []);
    else mkdir($dir, 0775, true);

    $src = tempnam(sys_get_temp_dir(), 'pd');
    makePreferredDealsXlsx($src);

    ReportProcessor::process([['name' => PD_NAME, 'path' => $src]], $dir);

    expect(is_file("$dir/PreferredDeals.xlsx"))->toBeTrue();
    expect(ZipBuilder::availableFiles($dir))->toContain('PreferredDeals.xlsx');

    // A later run without the file must not sweep it away.
    ReportProcessor::process([], $dir);
    expect(is_file("$dir/PreferredDeals.xlsx"))->toBeTrue();

    @unlink($src);
});
