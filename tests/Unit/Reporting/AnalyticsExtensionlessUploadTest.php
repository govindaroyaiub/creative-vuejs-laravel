<?php

use App\Services\Reporting\Extractors;

/**
 * Regression for a real, previously-silent bug: Extractors::analytics() used
 * to branch on pathinfo()'s file EXTENSION to decide CSV vs XLSX parsing. A
 * real HTTP upload's temp path (e.g. "phpB823.tmp") never carries the
 * original ".csv" extension, so every real browser upload of a GA4 CSV
 * analytics export always took the XLSX branch. That branch's own
 * PhpSpreadsheet CSV fallback then mis-detected the delimiter for this file
 * shape, silently returning zero parsed rows (no exception, no error) — a
 * real analytics upload for either site was doing nothing.
 */
it('parses a CSV analytics export even from an extensionless upload temp path', function () {
    $fixture = __DIR__ . '/../../Fixtures/Reporting/uploads/Analytics.csv';

    // Same shape as a real Laravel UploadedFile::getRealPath() temp file —
    // no ".csv" extension, unlike the fixture path itself.
    $tmp = tempnam(sys_get_temp_dir(), 'php');
    copy($fixture, $tmp);

    $rows = Extractors::analytics($tmp);
    unlink($tmp);

    expect($rows)->not->toBeEmpty();
    expect($rows[0])->toHaveKeys(['date', 'views', 'activeUsers', 'totalRevenue']);
});
