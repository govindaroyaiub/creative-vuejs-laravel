<?php

namespace App\Services\Reporting;

use Carbon\CarbonImmutable;

/**
 * Orchestrates an upload run, ported 1:1 from server.js /process:
 *   detect → parse per partner → group adhese per site → strip past-month rows
 *   (current month + last 7 days only) → merge per site → persist → regenerate
 *   the canonical uploaded files and the Analytics/Adhese CSVs.
 *
 * @param array<int, array{name:string, path:string}> $files
 */
class ReportProcessor
{
    public static function process(array $files, string $uploadsDir, ?CarbonImmutable $today = null): array
    {
        if (! is_dir($uploadsDir)) mkdir($uploadsDir, 0775, true);
        $today ??= CarbonImmutable::now();

        $store = ReportStore::load();
        // Ogury is in USD: convert per day with the ECB USD→EUR rate (see CurrencyRates).
        $usdToEur = fn (string $dateKey): float => CurrencyRates::usdToEur($dateKey);
        $errors = [];
        $filePatterns = (array) \App\Models\ReportSetting::get('file_patterns', []);

        $fileTypes = [];
        $pathByType = [];      // type => temp path of the uploaded file
        $origByType = [];      // type => original filename (for extension)
        $adheseRows = [];
        $analyticsFiles = [];  // list of {name, path} — one GA4 property export per site
        $gamF1mFiles = [];     // list of {name, path} — one GAM ad-requests export per site

        foreach ($files as $file) {
            $type = Reporting::detectFileType($file['name'], $filePatterns);
            $fileTypes[$file['name']] = $type;
            try {
                if ($type === 'adhese') {
                    $adheseRows = array_merge($adheseRows, Extractors::adhese($file['path'], $file['name']));
                } elseif ($type === 'analytics') {
                    $analyticsFiles[] = $file;
                } elseif ($type === 'gam_f1m') {
                    $gamF1mFiles[] = $file;
                } else {
                    $pathByType[$type] = $file['path'];
                    $origByType[$type] = $file['name'];
                }
            } catch (\Throwable $e) {
                // Skip an unparseable file; the rest of the run continues.
            }
        }

        // Group adhese rows by site, remembering each site's market.name exactly as
        // the uploaded file states it — the Adhese download writes it back out.
        $adhesePerSite = [];
        $adheseMarkets = [];
        foreach ($adheseRows as $row) {
            $siteId = self::siteForDomain($row['site']);
            if (! $siteId) continue;
            $k = Reporting::dateKey($row['date']);
            $adhesePerSite[$siteId][$k] ??= ['date' => $row['date'], 'revenue' => 0.0];
            $adhesePerSite[$siteId][$k]['revenue'] += $row['revenue'];
            if (($row['market'] ?? '') !== '') $adheseMarkets[$siteId] = $row['market'];
        }
        if ($adheseMarkets) {
            $store['config']['adheseMarkets'] = array_merge($store['config']['adheseMarkets'] ?? [], $adheseMarkets);
            \App\Models\ReportSetting::put('adhese_markets', $store['config']['adheseMarkets']);
        }

        // GA4 exports carry no site column (one property per file), so route by
        // filename the same way Adhese does: explicit TG/FL, default F1Maximaal.
        $analyticsPerSite = [];
        foreach ($analyticsFiles as $file) {
            $fname = mb_strtolower($file['name']);
            if (str_contains($fname, ' tg') || str_contains($fname, 'topgear')) $siteId = 'topgear';
            elseif (str_contains($fname, ' fl') || str_contains($fname, 'festileaks')) $siteId = 'festileaks';
            else $siteId = 'f1maximaal';
            try {
                $rows = Extractors::analytics($file['path']);
                $analyticsPerSite[$siteId] = array_merge($analyticsPerSite[$siteId] ?? [], $rows);
            } catch (\Throwable $e) {
                // Skip an unparseable file; the rest of the run continues.
            }
        }

        // GAM's per-site ad-requests export ("Copy of {site} …") also carries no
        // site column — one export per site — so route by filename the same way.
        $gamF1mPerSite = [];
        foreach ($gamF1mFiles as $file) {
            $fname = mb_strtolower($file['name']);
            $siteId = null;
            foreach (Reporting::SITES as $sid => $site) {
                $needle = mb_strtolower(explode('.', $site['name'])[0]);
                if (str_contains($fname, $needle)) { $siteId = $sid; break; }
            }
            $siteId ??= 'f1maximaal'; // legacy files never carried a site hint beyond "f1max"
            try {
                $rows = Extractors::gamF1m($file['path']);
                $gamF1mPerSite[$siteId] = array_merge($gamF1mPerSite[$siteId] ?? [], $rows);
            } catch (\Throwable $e) {
                // Skip an unparseable file; the rest of the run continues.
            }
        }

        // The Download Reports list shows exactly this run's files: start from an
        // empty folder, so nothing from an earlier upload (or a stale legacy name)
        // carries over.
        foreach (array_diff(scandir($uploadsDir) ?: [], ['.', '..']) as $old) {
            if (is_file($uploadsDir . '/' . $old)) unlink($uploadsDir . '/' . $old);
        }

        $thisMonth = $today->format('Y-m');
        $sevenDaysAgoKey = $today->subDays(7)->format('Y-m-d');

        $outbrainHasData = false;

        foreach (array_keys(Reporting::SITES) as $siteId) {
            $parsed = [];
            try {
                if (isset($pathByType['seedtag'])) $parsed['seedtag'] = Extractors::seedtag($pathByType['seedtag'], $siteId);
                if (isset($pathByType['teads'])) $parsed['teads'] = Extractors::teads($pathByType['teads'], $siteId);
                if (isset($pathByType['showheroes'])) $parsed['showheroes'] = Extractors::showheroes($pathByType['showheroes'], $siteId);
                if (isset($pathByType['gam'])) $parsed['gam'] = Extractors::gam($pathByType['gam'], $siteId);
                if (isset($pathByType['adform'])) $parsed['adform'] = Extractors::adform($pathByType['adform'], $siteId);
                // Own try: a failed rate lookup must skip only Ogury, not the whole
                // site, and is reported back instead of silently dropping the data.
                if (isset($pathByType['ogury'])) {
                    try {
                        $parsed['ogury'] = Extractors::ogury($pathByType['ogury'], $siteId, $usdToEur);
                    } catch (\Throwable $e) {
                        $errors['ogury'] = 'Ogury not processed: ' . $e->getMessage();
                    }
                }
                // Adsense + GumGum: one multi-site file each; the extractor filters
                // to this site's host, so they slot straight into the per-site loop.
                if (isset($pathByType['adsense'])) $parsed['adsense'] = Extractors::adsense($pathByType['adsense'], $siteId);
                if (isset($pathByType['gumgum'])) $parsed['gumgum'] = Extractors::gumgum($pathByType['gumgum'], $siteId);
                if (isset($adhesePerSite[$siteId])) $parsed['adhese'] = array_values($adhesePerSite[$siteId]);

                if (isset($analyticsPerSite[$siteId])) $parsed['analytics'] = $analyticsPerSite[$siteId];
                if (isset($gamF1mPerSite[$siteId])) $parsed['gam_f1m'] = $gamF1mPerSite[$siteId];

                if ($siteId === 'f1maximaal') {
                    if (isset($pathByType['outbrain'])) {
                        $parsed['outbrain'] = Extractors::outbrain(file_get_contents($pathByType['outbrain']));
                        $outbrainHasData = self::anyImpressions($parsed['outbrain']);
                    }
                    if (isset($pathByType['preferreddeals'])) {
                        $parsed['preferreddeals'] = Extractors::preferredDeals($pathByType['preferreddeals']);
                    }
                    if (isset($pathByType['impressions_f1'])) $parsed['impressions_f1'] = Extractors::impressionsF1($pathByType['impressions_f1']);
                }

                // Strip rows outside "current month + trailing week of the prior
                // month" before merging. The trailing-week check is bounded above
                // by the current month's start — without that bound, a file whose
                // rows are lexicographically >= $sevenDaysAgoKey but from a LATER
                // month (e.g. running a backdated backfill against a file that
                // also happens to carry more recent rows) would silently slip
                // through and merge into a month nobody asked to touch.
                foreach ($parsed as $key => $arr) {
                    if (! is_array($arr)) continue;
                    $parsed[$key] = array_values(array_filter($arr, function ($r) use ($thisMonth, $sevenDaysAgoKey) {
                        $dk = isset($r['date']) ? Reporting::dateKey($r['date']) : null;
                        return $dk && (str_starts_with($dk, $thisMonth) || ($dk >= $sevenDaysAgoKey && $dk < $thisMonth . '-01'));
                    }));
                }

                if (count($parsed) > 0) StoreMerger::merge($store, $siteId, $parsed);
            } catch (\Throwable $e) {
                // One site failing must not abort the others.
            }
        }

        ReportStore::save($store);

        // Re-save canonical partner files for the ZIP download.
        foreach ($pathByType as $type => $path) {
            if ($type === 'analytics') continue;
            $baseName = Reporting::RENAME_MAP[$type] ?? null;
            if (! $baseName) continue;
            $ext = '.' . (pathinfo($origByType[$type] ?? '', PATHINFO_EXTENSION) ?: 'xlsx');
            copy($path, $uploadsDir . '/' . $baseName . $ext);
        }

        if (isset($pathByType['outbrain']) && $outbrainHasData) {
            $ext = '.' . (pathinfo($origByType['outbrain'] ?? '', PATHINFO_EXTENSION) ?: 'csv');
            copy($pathByType['outbrain'], $uploadsDir . '/Outbrain' . $ext);
        }

        // Generated CSVs only for the sites whose Adhese / Analytics file was in this
        // upload. Analytics is only wired for F1 and Topgear (Festileaks isn't part
        // of the Adhese/RPM bundle).
        foreach (['f1maximaal' => 'f1', 'topgear' => 'tg', 'festileaks' => 'fl', 'jfk' => 'jfk'] as $sid => $label) {
            if (isset($adhesePerSite[$sid])) {
                file_put_contents($uploadsDir . "/Adhese {$label}.csv", CsvGenerator::adhese($store, $sid));
            }
        }
        foreach (['f1maximaal' => 'f1', 'topgear' => 'tg'] as $sid => $label) {
            $csv = isset($analyticsPerSite[$sid]) ? CsvGenerator::analytics($store, $sid) : '';
            if ($csv !== '') {
                file_put_contents($uploadsDir . "/Analytics {$label}.csv", $csv);
            }
        }

        return [
            'fileTypes' => $fileTypes,
            'errors' => array_values($errors),
            'store' => $store,
            'analyticsCSV' => CsvGenerator::analytics($store),
            'adheseCSV' => CsvGenerator::adhese($store, 'f1maximaal'),
        ];
    }

    private static function siteForDomain(string $domain): ?string
    {
        foreach (array_keys(Reporting::SITES) as $id) {
            if (Reporting::siteHostMatches($domain, $id)) return $id;
        }
        return null;
    }

    private static function anyImpressions(array $rows): bool
    {
        foreach ($rows as $r) {
            if (($r['impressions'] ?? 0) > 0) return true;
        }
        return false;
    }
}
