<?php

namespace App\Services\Reporting;

use App\Models\ReportSetting;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Daily USD → EUR rates for converting Ogury revenue (Ogury reports in USD).
 *
 * The Go ReportingServer converts with openexchangerates.org rates that the
 * EasyAdmin cron stores per day. We don't have that key, so we use the European
 * Central Bank reference rates via the free Frankfurter API instead. They differ
 * from openexchangerates by ~0.1–0.3%, which keeps Ogury within a few cents per
 * day of the Go report (well inside the Verifier's €0.50 tolerance).
 *
 * The ECB publishes no rate on weekends/holidays; those days take the latest
 * earlier business-day rate. Rates are cached in report_settings
 * ('usd_eur_rates', date => rate) so they're fetched once and travel with the
 * reporting JSON export like the rest of the report data.
 */
class CurrencyRates
{
    private const SETTING_KEY = 'usd_eur_rates';
    private const API = 'https://api.frankfurter.app';
    /** How far back to look for a business-day rate (covers long holiday weekends). */
    private const LOOKBACK_DAYS = 10;

    /** @var array<string, float>|null date => rate, loaded once per request */
    private static ?array $cache = null;

    /** @var list<array{0:string,1:string}> [from, to] windows already fetched this request */
    private static array $fetched = [];

    /** USD → EUR rate for the given date (Y-m-d or Carbon). */
    public static function usdToEur(CarbonImmutable|string $date): float
    {
        $key = $date instanceof CarbonImmutable ? $date->format('Y-m-d') : $date;
        self::$cache ??= (array) ReportSetting::get(self::SETTING_KEY, []);

        if (isset(self::$cache[$key])) return (float) self::$cache[$key];

        // Not cached exactly: fetch first (unless this request already fetched a
        // window covering it), so a stale earlier day is never used in place of a
        // business day whose rate simply hasn't been fetched yet.
        if (! self::covered($key)) self::fetchAround($key);
        $rate = self::resolve($key);
        if ($rate === null) {
            throw new RuntimeException("No USD→EUR rate available for {$key}");
        }

        // Remember the resolved rate for past dates only: for today the ECB rate
        // may not be published yet, so a carried-forward value must not stick.
        if ($key < CarbonImmutable::today()->format('Y-m-d') && ! isset(self::$cache[$key])) {
            self::$cache[$key] = $rate;
            self::persist();
        }

        return $rate;
    }

    /** Forget the in-memory cache (tests). */
    public static function flush(): void
    {
        self::$cache = null;
        self::$fetched = [];
    }

    private static function covered(string $key): bool
    {
        foreach (self::$fetched as [$from, $to]) {
            if ($key >= $from && $key <= $to) return true;
        }

        return false;
    }

    /** Latest known rate on or before $key, within the lookback window; null if none. */
    private static function resolve(string $key): ?float
    {
        $d = CarbonImmutable::parse($key);
        for ($i = 0; $i <= self::LOOKBACK_DAYS; $i++) {
            $k = $d->subDays($i)->format('Y-m-d');
            if (isset(self::$cache[$k])) return (float) self::$cache[$k];
        }

        return null;
    }

    /**
     * Fetch every ECB rate from LOOKBACK_DAYS before $key up to ~6 weeks after it
     * (capped at today) in one request, so a whole month of Ogury rows costs a
     * single API call.
     */
    private static function fetchAround(string $key): void
    {
        $d = CarbonImmutable::parse($key);
        $from = $d->subDays(self::LOOKBACK_DAYS)->format('Y-m-d');
        $to = $d->addDays(45)->min(CarbonImmutable::today())->format('Y-m-d');
        if ($to < $from) $to = $from;

        try {
            $res = Http::timeout(15)->retry(2, 500)
                ->get(self::API . "/{$from}..{$to}", ['from' => 'USD', 'to' => 'EUR']);
        } catch (\Throwable $e) {
            throw new RuntimeException("Could not fetch USD→EUR rates for {$key}: {$e->getMessage()}", 0, $e);
        }
        if (! $res->successful()) {
            throw new RuntimeException("Could not fetch USD→EUR rates for {$key}: HTTP {$res->status()}");
        }

        self::$fetched[] = [$from, $to];
        $added = false;
        foreach ((array) $res->json('rates', []) as $date => $quotes) {
            if (isset($quotes['EUR']) && ! isset(self::$cache[$date])) {
                self::$cache[$date] = (float) $quotes['EUR'];
                $added = true;
            }
        }
        if ($added) self::persist();
    }

    private static function persist(): void
    {
        ksort(self::$cache);
        ReportSetting::put(self::SETTING_KEY, self::$cache);
    }
}
