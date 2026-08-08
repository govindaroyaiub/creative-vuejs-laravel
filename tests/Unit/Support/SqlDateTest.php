<?php

use App\Models\Bill;
use App\Support\SqlDate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('formats a long date the way MySQL DATE_FORMAT does', function () {
    Bill::factory()->create(['created_at' => '2026-08-07 10:00:00']);

    $formatted = DB::table('bills')
        ->selectRaw(SqlDate::longDate('created_at') . ' as label')
        ->value('label');

    expect($formatted)->toBe('07 August 2026');
});

it('spells every month name out', function () {
    foreach (range(1, 12) as $month) {
        Bill::factory()->create([
            'created_at' => sprintf('2026-%02d-15 10:00:00', $month),
        ]);
    }

    $labels = DB::table('bills')
        ->selectRaw(SqlDate::longDate('created_at') . ' as label')
        ->orderBy('created_at')
        ->pluck('label')
        ->all();

    expect($labels)->toBe([
        '15 January 2026', '15 February 2026', '15 March 2026', '15 April 2026',
        '15 May 2026', '15 June 2026', '15 July 2026', '15 August 2026',
        '15 September 2026', '15 October 2026', '15 November 2026', '15 December 2026',
    ]);
});

it('extracts the month as a groupable integer', function () {
    Bill::factory()->create(['created_at' => '2026-01-05 10:00:00']);
    Bill::factory()->create(['created_at' => '2026-01-19 10:00:00']);
    Bill::factory()->create(['created_at' => '2026-11-02 10:00:00']);

    $counts = DB::table('bills')
        ->selectRaw(SqlDate::month('created_at') . ' as month, COUNT(*) as count')
        ->groupBy('month')
        ->orderBy('month')
        ->pluck('count', 'month')
        ->all();

    // Keys must be plain integers, not zero-padded strings, because the
    // dashboard buckets months by `intval($row->month)`.
    expect(array_map('intval', array_keys($counts)))->toBe([1, 11]);
    expect(array_values($counts))->toBe([2, 1]);
});
