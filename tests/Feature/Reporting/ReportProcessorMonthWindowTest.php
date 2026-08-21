<?php

use App\Models\ReportDay;
use App\Services\Reporting\ReportProcessor;
use Carbon\CarbonImmutable;

/**
 * Regression for a real leak: ReportProcessor::process() strips rows outside
 * "current month + trailing week of the prior month" before merging. That
 * trailing-week check used to have no upper bound (`$dk >= $sevenDaysAgoKey`),
 * so when `$today` is deliberately backdated to backfill an old month (e.g.
 * March), any row from a LATER month lexicographically ordered after the
 * cutoff (e.g. May) slipped through and got merged too — even though nobody
 * asked to touch May. See the March-2026 Topgear/F1 backfill session.
 */
it('does not merge rows from a later month when today is backdated for a historical backfill', function () {
    $dir = storage_path('framework/testing/month-window-test');
    if (! is_dir($dir)) mkdir($dir, 0775, true);
    $path = "$dir/Adhese tg.csv";
    file_put_contents($path, "date,market.name,Paid Revenue\n\"Mar 15, 2026\",DALE-igmn,12.34\n\"May 10, 2026\",DALE-igmn,56.78\n");

    ReportProcessor::process(
        [['name' => 'Adhese tg.csv', 'path' => $path]],
        storage_path('framework/testing/month-window-uploads'),
        CarbonImmutable::create(2026, 3, 31), // backdated: backfilling March
    );

    $march = ReportDay::firstWhere(['site' => 'topgear', 'date' => '2026-03-15']);
    expect($march)->not->toBeNull();
    expect(round($march->revenue['adhese'], 2))->toBe(12.34);

    // The May row must NOT have slipped through just because "May" sorts
    // after the March cutoff string — it's a different month nobody asked for.
    $may = ReportDay::firstWhere(['site' => 'topgear', 'date' => '2026-05-10']);
    expect($may === null || ($may->revenue['adhese'] ?? 0) == 0)->toBeTrue();

    unlink($path);
});
