<?php

use App\Models\ReportDay;
use App\Models\ReportSetting;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;

/**
 * HTTP-level coverage for the Reporting module endpoints: upload processing,
 * monthly verification, config, and the ZIP download — exercised through the
 * real route + permission middleware as a super-admin (permissions: ['*']).
 */
$fixtures = __DIR__ . '/../../Fixtures/Reporting';

beforeEach(function () {
    // Pin "now" inside the June-2026 fixture window. ReportProcessor strips any row
    // outside the current month + last 7 days, so without this the June fixtures get
    // wiped once the real clock moves past that window and the assertions see 0 rows.
    $this->travelTo(CarbonImmutable::create(2026, 6, 25));
    createAuthenticatedUser(['permissions' => ['*'], 'email_verified_at' => now()]);
});

$uploadFile = fn (string $path, string $name) => new UploadedFile($path, $name, null, null, true);

$partnerFiles = function () use ($fixtures, $uploadFile) {
    $dir = "$fixtures/uploads";
    // The fixtures are stored under canonical names, but uploads arrive with the
    // partners' ORIGINAL filenames — which is what detectFileType keys off. Same
    // bytes, original-style name.
    $map = [
        'Adform.xlsx'     => 'TG 2_25-06-2026.xlsx',
        'GAM.xlsx'        => 'Copy of General Data Download for Publishers.xlsx',
        'Ogury.xlsx'      => 'export-ad-units-2026-06-01-2026-06-24.xlsx',
        'SeedTag.xlsx'    => 'revenue-export-1782397575032.xlsx',
        'Showheroes.xlsx' => 'TopGear--EUR-2026-06-25T1427.xlsx',
        'Teads.xlsx'      => 'report_finance_06-25-26-202711.xlsx',
        'Analytics.csv'   => 'Pages_and_screens_Content_group.csv',
        'Adhese f1.csv'   => 'Adhese f1.csv',
        'Adhese fl.csv'   => 'Adhese fl.csv',
        'Adhese tg.csv'   => 'Adhese tg.csv',
    ];
    return array_map(fn ($fixtureName, $uploadName) => $uploadFile("$dir/$fixtureName", $uploadName),
        array_keys($map), array_values($map));
};

it('processes an upload run and persists the data', function () use ($partnerFiles) {
    $res = $this->post('/reporting/process', ['files' => $partnerFiles()]);

    $res->assertRedirect(route('reporting'));
    expect(ReportDay::where('site', 'f1maximaal')->count())->toBeGreaterThan(0);

    $day = ReportDay::firstWhere(['site' => 'f1maximaal', 'date' => '2026-06-01']);
    expect(round($day->revenue['showheroes'], 2))->toBe(20.27); // the column that regressed in prod
});

it('downloads a ZIP after processing', function () use ($partnerFiles) {
    $this->post('/reporting/process', ['files' => $partnerFiles()])->assertRedirect();

    $res = $this->get('/reporting/download');
    $res->assertOk();
    expect($res->headers->get('content-type'))->toContain('application/zip');
});

it('verifies the monthly Planetnine report', function () use ($partnerFiles, $uploadFile, $fixtures) {
    $this->post('/reporting/process', ['files' => $partnerFiles()])->assertRedirect();

    $this->post('/reporting/verify', [
        'file' => $uploadFile("$fixtures/Planetnine-Report.xlsx", 'Planetnine-Report.xlsx'),
    ])->assertRedirect(route('reporting'))->assertSessionHas('verifyResult');

    $result = session('verifyResult');
    expect($result['rows'])->not->toBeEmpty();

    // A day's Showheroes check should now reconcile (pn ≈ us) since the port matches.
    $row = collect($result['rows'])->firstWhere('dateKey', '2026-06-01');
    $sh = collect($row['checks'])->firstWhere('label', 'Showheroes');
    expect(abs($sh['pn'] - $sh['us']))->toBeLessThan(0.5);
});

it('updates the ogury rate', function () {
    $this->post('/reporting/config', ['oguryRate' => 0.9])->assertRedirect(route('reporting'));
    expect((float) ReportSetting::get('oguryRate'))->toEqual(0.9);
});

it('saves manual adhese impressions and recomputes impressions sold', function () use ($partnerFiles) {
    $this->post('/reporting/process', ['files' => $partnerFiles()])->assertRedirect();

    $this->post('/reporting/save-adhese', ['dateKey' => '2026-06-01', 'adhese' => 12345])
        ->assertRedirect(route('reporting'));

    $day = ReportDay::firstWhere(['site' => 'f1maximaal', 'date' => '2026-06-01']);
    expect($day->impressions['adhese'])->toBe(12345);
    expect($day->impressions_sold)->toBeGreaterThan(12345);
});

it('blocks unauthenticated access to the reporting page', function () {
    auth()->logout();
    $this->get('/reporting')->assertRedirect();
});

it('records and exposes the missing-files list from a partial upload', function () use ($fixtures, $uploadFile) {
    $dir = "$fixtures/uploads";
    // Upload only SeedTag + Teads (original-style names) — everything else is missing.
    $this->post('/reporting/process', ['files' => [
        $uploadFile("$dir/SeedTag.xlsx", 'revenue-export-1782397575032.xlsx'),
        $uploadFile("$dir/Teads.xlsx", 'report_finance_06-25-26-202711.xlsx'),
    ]])->assertRedirect(route('reporting'));

    $lastRun = \App\Models\ReportSetting::get('last_run');
    expect($lastRun['missing'])->toContain('Adform', 'GAM', 'Showheroes', 'Ogury', 'Adhese (F1)');
    expect($lastRun['missing'])->not->toContain('SeedTag', 'Teads');

    $this->get('/reporting')->assertInertia(fn (\Inertia\Testing\AssertableInertia $p) =>
        $p->component('Reporting/Index')->has('lastRun.missing')
    );
});
