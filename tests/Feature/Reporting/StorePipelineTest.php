<?php

use App\Models\ReportDay;
use App\Services\Reporting\Extractors;
use App\Services\Reporting\Reporting;
use App\Services\Reporting\ReportStore;
use App\Services\Reporting\StoreMerger;

/**
 * End-to-end: parse the fixture partner files, merge per site, persist to the DB,
 * reload, and confirm the numbers survive the round-trip. Exercises StoreMerger +
 * ReportStore against a real (sqlite) database.
 */
$uploads = __DIR__ . '/../../Fixtures/Reporting/uploads';
$rate = 0.855;

$buildStore = function () use ($uploads, $rate): array {
    $store = ReportStore::empty();
    $store['config']['oguryRate'] = $rate;

    // Adhese grouped per site across all adhese files (mirrors /process).
    $domainToId = ['f1maximaal.nl' => 'f1maximaal', 'topgear.nl' => 'topgear', 'horses.nl' => 'horses', 'festileaks.com' => 'festileaks'];
    $adhesePerSite = [];
    foreach (scandir($uploads) as $f) {
        if (Reporting::detectFileType($f) !== 'adhese') continue;
        foreach (Extractors::adhese(file_get_contents("$uploads/$f"), $f) as $row) {
            $id = $domainToId[$row['site']] ?? null;
            if (! $id) continue;
            $k = Reporting::dateKey($row['date']);
            $adhesePerSite[$id][$k] ??= ['date' => $row['date'], 'revenue' => 0.0];
            $adhesePerSite[$id][$k]['revenue'] += $row['revenue'];
        }
    }

    foreach (array_keys(Reporting::SITES) as $site) {
        $parsed = [
            'seedtag' => Extractors::seedtag("$uploads/SeedTag.xlsx", $site),
            'teads' => Extractors::teads("$uploads/Teads.xlsx", $site),
            'showheroes' => Extractors::showheroes("$uploads/Showheroes.xlsx", $site),
            'gam' => Extractors::gam("$uploads/GAM.xlsx", $site),
            'adform' => Extractors::adform("$uploads/Adform.xlsx", $site),
            'ogury' => Extractors::ogury("$uploads/Ogury.xlsx", $site, $rate),
        ];
        if (isset($adhesePerSite[$site])) $parsed['adhese'] = array_values($adhesePerSite[$site]);
        if ($site === 'f1maximaal') $parsed['analytics'] = Extractors::analytics(file_get_contents("$uploads/Analytics.csv"));
        StoreMerger::merge($store, $site, $parsed);
    }

    return $store;
};

it('persists and reloads the merged store with matching numbers', function () use ($buildStore) {
    $store = $buildStore();
    ReportStore::save($store);

    // Rows landed for the expected sites (festileaks has no partner data here).
    expect(ReportDay::where('site', 'f1maximaal')->count())->toBeGreaterThanOrEqual(24);
    expect(ReportDay::where('site', 'topgear')->count())->toBeGreaterThan(0);
    expect(ReportDay::where('site', 'f1maximaal')->whereDate('date', '2026-06-01')->exists())->toBeTrue();

    $reloaded = ReportStore::load();

    $memDay = $store['sites']['f1maximaal']['days']['2026-06-01'];
    $dbDay = $reloaded['sites']['f1maximaal']['days']['2026-06-01'];

    // Showheroes revenue (the column that regressed in prod) survives the round-trip.
    expect(round($dbDay['revenue']['showheroes'], 2))->toBe(20.27);
    expect($dbDay['revenue']['showheroes'])->toEqualWithDelta($memDay['revenue']['showheroes'], 1e-6);
    // DB stores impressions_sold as an integer column; in-memory it's a float sum.
    expect($dbDay['impressionsSold'])->toEqual((int) round($memDay['impressionsSold']));
    expect($dbDay['impressionsSold'])->toBeGreaterThan(0);

    // Config persisted.
    expect($reloaded['config']['oguryRate'])->toEqual(0.855);
});

it('carries forward a partner that is missing from a later run', function () use ($buildStore) {
    // First run: full data saved.
    ReportStore::save($buildStore());
    $before = ReportDay::firstWhere(['site' => 'f1maximaal', 'date' => '2026-06-01'])->revenue['showheroes'];
    expect(round($before, 2))->toBe(20.27);

    // Second run: NO showheroes file this time. Re-merge from the loaded store.
    $store = ReportStore::load();
    StoreMerger::merge($store, 'f1maximaal', ['seedtag' => []]); // a run with no showheroes
    ReportStore::save($store);

    $after = ReportDay::firstWhere(['site' => 'f1maximaal', 'date' => '2026-06-01'])->revenue['showheroes'];
    expect(round($after, 2))->toBe(20.27); // unchanged, not zeroed
});
