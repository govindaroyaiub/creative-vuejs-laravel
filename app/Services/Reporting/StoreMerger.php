<?php

namespace App\Services\Reporting;

/**
 * Merges parsed partner data into the in-memory store, ported 1:1 from
 * server.js mergeIntoStore. The critical invariant: a partner missing from this
 * run is NOT zeroed — its prior stored value carries forward (?? existing ?? 0).
 *
 * Store shape (matches the JS store):
 *   [ 'sites' => [ 'f1maximaal' => [ 'days' => [ 'YYYY-MM-DD' => day ] ], ... ],
 *     'config' => [...] ]
 *
 * $parsed is keyed by partner: adhese, seedtag, teads, showheroes, gam, adform,
 * ogury, analytics, gam_f1m, outbrain, preferreddeals, impressions_f1 — each a
 * list of extractor rows carrying a CarbonImmutable 'date'.
 */
class StoreMerger
{
    public static function merge(array &$store, string $siteId, array $parsed): void
    {
        if (! isset($store['sites'][$siteId])) return;
        $days = &$store['sites'][$siteId]['days'];
        if (! is_array($days)) $days = [];

        $lookup = function (?array $arr): array {
            $m = [];
            foreach ($arr ?? [] as $r) $m[Reporting::dateKey($r['date'])] = $r;
            return $m;
        };

        $adhese = $lookup($parsed['adhese'] ?? null);
        $seedtag = $lookup($parsed['seedtag'] ?? null);
        $teads = $lookup($parsed['teads'] ?? null);
        $showheroes = $lookup($parsed['showheroes'] ?? null);
        $gam = $lookup($parsed['gam'] ?? null);
        $adform = $lookup($parsed['adform'] ?? null);
        $ogury = $lookup($parsed['ogury'] ?? null);

        $isF1 = $siteId === 'f1maximaal';
        $analytics = $isF1 ? $lookup($parsed['analytics'] ?? null) : [];
        $gamF1m = $isF1 ? $lookup($parsed['gam_f1m'] ?? null) : [];
        $outbrain = $isF1 ? $lookup($parsed['outbrain'] ?? null) : [];
        $preferred = $isF1 ? $lookup($parsed['preferreddeals'] ?? null) : [];
        // impressions_f1 was historically wired but Adhese impressions are entered
        // manually, so it is parsed but not used to overwrite. Kept for parity.

        $allKeys = array_unique(array_merge(
            array_keys($adhese), array_keys($seedtag), array_keys($teads), array_keys($showheroes),
            array_keys($gam), array_keys($adform), array_keys($ogury), array_keys($analytics),
            array_keys($gamF1m), array_keys($outbrain), array_keys($preferred),
        ));

        foreach ($allKeys as $k) {
            $existing = $days[$k] ?? [];
            $day = $existing;
            $day['dateKey'] = $k;
            $day['revenue'] = [
                'adhese' => $adhese[$k]['revenue'] ?? $existing['revenue']['adhese'] ?? 0,
                'gam' => $gam[$k]['revenue'] ?? $existing['revenue']['gam'] ?? 0,
                'seedtag' => $seedtag[$k]['revenue'] ?? $existing['revenue']['seedtag'] ?? 0,
                'teads' => $teads[$k]['revenue'] ?? $existing['revenue']['teads'] ?? 0,
                'showheroes' => $showheroes[$k]['revenue'] ?? $existing['revenue']['showheroes'] ?? 0,
                'adform' => $adform[$k]['revenue'] ?? $existing['revenue']['adform'] ?? 0,
                'ogury' => $ogury[$k]['revenue'] ?? $existing['revenue']['ogury'] ?? 0,
                'outbrain' => $outbrain[$k]['revenue'] ?? $existing['revenue']['outbrain'] ?? 0,
                'preferredDeals' => $preferred[$k]['revenue'] ?? $existing['revenue']['preferredDeals'] ?? 0,
            ];

            if ($isF1) {
                $gf = $gamF1m[$k] ?? [];
                $imp = $existing['impressions'] ?? [];
                $day['impressions'] = [
                    'seedtag' => $seedtag[$k]['impressions'] ?? $imp['seedtag'] ?? 0,
                    'teads' => $teads[$k]['impressions'] ?? $imp['teads'] ?? 0,
                    'showheroes' => $showheroes[$k]['impressions'] ?? $imp['showheroes'] ?? 0,
                    'gam' => $gf['gamImpressions'] ?? $imp['gam'] ?? 0,
                    'adform' => $adform[$k]['impressions'] ?? $imp['adform'] ?? 0,
                    'ogury' => $ogury[$k]['impressions'] ?? $imp['ogury'] ?? 0,
                    'outbrain' => $outbrain[$k]['impressions'] ?? $imp['outbrain'] ?? 0,
                    // Adhese impressions are entered manually; never overwrite from a file.
                    'adhese' => $imp['adhese'] ?? null,
                    'preferredDeals' => $preferred[$k]['impressions'] ?? $imp['preferredDeals'] ?? 0,
                ];
                $day['totalAdRequests'] = $gf['totalAdRequests'] ?? $existing['totalAdRequests'] ?? 0;
                $day['analytics'] = isset($analytics[$k]) ? [
                    'views' => $analytics[$k]['views'],
                    'activeUsers' => $analytics[$k]['activeUsers'],
                    'viewsPerUser' => $analytics[$k]['viewsPerUser'],
                    'avgEngagement' => $analytics[$k]['avgEngagement'],
                    'eventCount' => $analytics[$k]['eventCount'],
                    'keyEvents' => $analytics[$k]['keyEvents'],
                    'totalRevenue' => $analytics[$k]['totalRevenue'],
                ] : ($existing['analytics'] ?? null);

                $i2 = $day['impressions'];
                $day['impressionsSold'] = ($i2['seedtag'] ?: 0) + ($i2['teads'] ?: 0) + ($i2['showheroes'] ?: 0)
                    + ($i2['gam'] ?: 0) + ($i2['adform'] ?: 0) + ($i2['ogury'] ?: 0)
                    + ($i2['outbrain'] ?: 0) + ($i2['adhese'] ?: 0) + ($i2['preferredDeals'] ?: 0);
            }

            $days[$k] = $day;
        }
    }
}
