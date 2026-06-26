<?php

use App\Models\ReportDay;
use App\Models\User;

/**
 * The Table tab can save manual Adhese impressions for many days at once via
 * /reporting/save-adhese-batch. Each day's impressions_sold is recomputed.
 */
beforeEach(function () {
    $u = User::factory()->create(['permissions' => ['*'], 'email_verified_at' => now()]);
    $this->actingAs($u);
});

it('saves adhese impressions for multiple days and recomputes impressions sold', function () {
    ReportDay::create([
        'site' => 'f1maximaal', 'date' => '2026-06-01',
        'revenue' => [], 'impressions' => ['seedtag' => 100, 'teads' => 50],
        'total_ad_requests' => 0, 'impressions_sold' => 150,
    ]);
    ReportDay::create([
        'site' => 'f1maximaal', 'date' => '2026-06-02',
        'revenue' => [], 'impressions' => ['gam' => 200],
        'total_ad_requests' => 0, 'impressions_sold' => 200,
    ]);

    $this->post('/reporting/save-adhese-batch', ['entries' => [
        ['dateKey' => '2026-06-01', 'adhese' => 1000],
        ['dateKey' => '2026-06-02', 'adhese' => 2000],
    ]])->assertRedirect(route('reporting'));

    $d1 = ReportDay::firstWhere(['site' => 'f1maximaal', 'date' => '2026-06-01']);
    expect($d1->impressions['adhese'])->toBe(1000);
    expect($d1->impressions_sold)->toBe(1150); // 100 + 50 + 1000

    $d2 = ReportDay::firstWhere(['site' => 'f1maximaal', 'date' => '2026-06-02']);
    expect($d2->impressions['adhese'])->toBe(2000);
    expect($d2->impressions_sold)->toBe(2200); // 200 + 2000
});

it('clears adhese when an entry is blank', function () {
    ReportDay::create([
        'site' => 'f1maximaal', 'date' => '2026-06-03',
        'revenue' => [], 'impressions' => ['seedtag' => 100, 'adhese' => 999],
        'total_ad_requests' => 0, 'impressions_sold' => 1099,
    ]);

    $this->post('/reporting/save-adhese-batch', ['entries' => [
        ['dateKey' => '2026-06-03', 'adhese' => null],
    ]])->assertRedirect(route('reporting'));

    $d = ReportDay::firstWhere(['site' => 'f1maximaal', 'date' => '2026-06-03']);
    expect($d->impressions['adhese'])->toBeNull();
    expect($d->impressions_sold)->toBe(100); // only seedtag remains
});

it('ignores entries for days that do not exist', function () {
    $this->post('/reporting/save-adhese-batch', ['entries' => [
        ['dateKey' => '2099-01-01', 'adhese' => 500],
    ]])->assertRedirect(route('reporting'));

    expect(ReportDay::where('site', 'f1maximaal')->count())->toBe(0);
});
