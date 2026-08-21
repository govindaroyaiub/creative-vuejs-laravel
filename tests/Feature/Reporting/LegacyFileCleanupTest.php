<?php

use App\Services\Reporting\ReportProcessor;

/**
 * storage/app/reporting/uploads is gitignored (per-machine), so a laptop that
 * hasn't run an upload since "Analytics.csv" was renamed to "Analytics f1.csv"
 * (and "Analytics fl.csv" dropped entirely) would otherwise show those stale
 * names in the download modal forever. ReportProcessor::process() should sweep
 * them away on every run, the same way it already does for Outbrain/PreferredDeals.
 */
it('deletes legacy Analytics filenames left over from before the rename', function () {
    $dir = storage_path('framework/testing/legacy-cleanup-uploads');
    if (! is_dir($dir)) mkdir($dir, 0775, true);
    file_put_contents("$dir/Analytics.csv", 'stale');
    file_put_contents("$dir/Analytics fl.csv", 'stale');

    ReportProcessor::process([], $dir);

    expect(is_file("$dir/Analytics.csv"))->toBeFalse();
    expect(is_file("$dir/Analytics fl.csv"))->toBeFalse();
});
