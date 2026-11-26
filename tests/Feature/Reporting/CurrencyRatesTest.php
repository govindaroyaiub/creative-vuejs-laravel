<?php

use App\Models\ReportSetting;
use App\Services\Reporting\CurrencyRates;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->travelTo(CarbonImmutable::create(2026, 11, 26, 10));
    CurrencyRates::flush();
});

// Fri 2026-11-20 and Mon 2026-11-23 are business days; the weekend between has no ECB rate.
$ecb = fn () => Http::response(['amount' => 1.0, 'base' => 'USD', 'rates' => [
    '2026-11-19' => ['EUR' => 0.90975],
    '2026-11-20' => ['EUR' => 0.90992],
    '2026-11-23' => ['EUR' => 0.91416],
]]);

it('returns the ECB rate for a business day', function () use ($ecb) {
    Http::fake(['api.frankfurter.app/*' => $ecb()]);

    expect(CurrencyRates::usdToEur('2026-11-23'))->toBe(0.91416);
    Http::assertSent(fn ($r) => str_contains($r->url(), 'from=USD') && str_contains($r->url(), 'to=EUR'));
});

it('uses the last business-day rate for weekends', function () use ($ecb) {
    Http::fake(['api.frankfurter.app/*' => $ecb()]);

    expect(CurrencyRates::usdToEur('2026-11-21'))->toBe(0.90992);
    expect(CurrencyRates::usdToEur('2026-11-22'))->toBe(0.90992);
});

it('fetches a whole window once and caches it in report settings', function () use ($ecb) {
    Http::fake(['api.frankfurter.app/*' => $ecb()]);

    foreach (['2026-11-19', '2026-11-20', '2026-11-21', '2026-11-22', '2026-11-23'] as $d) {
        CurrencyRates::usdToEur($d);
    }
    Http::assertSentCount(1);

    // A fresh request reads the cache, no new API call.
    CurrencyRates::flush();
    expect(CurrencyRates::usdToEur('2026-11-21'))->toBe(0.90992);
    Http::assertSentCount(1);
    expect(ReportSetting::get('usd_eur_rates'))->toHaveKeys(['2026-11-20', '2026-11-21', '2026-11-23']);
});

it('does not reuse an older cached day for a business day it has not fetched yet', function () {
    ReportSetting::put('usd_eur_rates', ['2026-11-19' => 0.90975]);
    Http::fake(['api.frankfurter.app/*' => Http::response(['rates' => ['2026-11-20' => ['EUR' => 0.90992]]])]);

    expect(CurrencyRates::usdToEur('2026-11-20'))->toBe(0.90992);
    Http::assertSentCount(1);
});

it('does not persist a carried-forward rate for today', function () use ($ecb) {
    Http::fake(['api.frankfurter.app/*' => $ecb()]);

    // Today (2026-11-26) has no ECB rate yet in the fake → carried from 11-23.
    expect(CurrencyRates::usdToEur('2026-11-26'))->toBe(0.91416);
    expect(ReportSetting::get('usd_eur_rates'))->not->toHaveKey('2026-11-26');
});

it('throws when no rate can be fetched', function () {
    Http::fake(['api.frankfurter.app/*' => Http::response('down', 503)]);

    CurrencyRates::usdToEur('2026-11-20');
})->throws(RuntimeException::class, 'USD→EUR');
