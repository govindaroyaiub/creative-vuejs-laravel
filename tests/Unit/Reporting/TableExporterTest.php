<?php

use App\Services\Reporting\TableExporter;

/**
 * Outbrain and Preferred Deals only ever run on F1Maximaal (extraction itself
 * is F1-only in ReportProcessor) — every other site would show dead zero
 * columns for both. TableExporter::data() should hide them there.
 */
it('includes Outbrain and Preferred Deals only for f1maximaal', function () {
    $store = [
        'sites' => [
            'f1maximaal' => ['days' => ['2026-03-01' => ['dateKey' => '2026-03-01', 'revenue' => ['outbrain' => 5.0]]]],
            'topgear' => ['days' => ['2026-03-01' => ['dateKey' => '2026-03-01', 'revenue' => ['gam' => 10.0]]]],
        ],
    ];

    $f1 = TableExporter::data($store, 'f1maximaal');
    expect(array_keys($f1['partners']))->toContain('outbrain', 'preferredDeals');
    expect($f1['days'][0]['revenue'])->toHaveKey('outbrain');

    $tg = TableExporter::data($store, 'topgear');
    expect(array_keys($tg['partners']))->not->toContain('outbrain', 'preferredDeals');
    expect($tg['days'][0]['revenue'])->not->toHaveKey('outbrain');
    expect($tg['totals']['revenue'])->not->toHaveKey('outbrain');

    // The formatted exports must follow the same filtered column set — no
    // "Outbrain"/"Preferred Deals" header for a non-F1 site.
    $csv = TableExporter::csv($tg);
    expect($csv)->not->toContain('Outbrain');
    $csvF1 = TableExporter::csv($f1);
    expect($csvF1)->toContain('Outbrain');
});
