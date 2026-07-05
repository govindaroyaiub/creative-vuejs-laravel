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
        $oguryRate = $store['config']['oguryRate'] ?? ReportStore::DEFAULT_OGURY_RATE;
        $filePatterns = (array) \App\Models\ReportSetting::get('file_patterns', []);

        $fileTypes = [];
        $pathByType = [];      // type => temp path of the uploaded file
        $origByType = [];      // type => original filename (for extension)
        $adheseRows = [];

        foreach ($files as $file) {
            $type = Reporting::detectFileType($file['name'], $filePatterns);
            $fileTypes[$file['name']] = $type;
            try {
                if ($type === 'adhese') {
                    $adheseRows = array_merge($adheseRows, Extractors::adhese($file['path'], $file['name']));
                } else {
                    $pathByType[$type] = $file['path'];
                    $origByType[$type] = $file['name'];
                }
            } catch (\Throwable $e) {
                // Skip an unparseable file; the rest of the run continues.
            }
        }

        // Group adhese rows by site.
        $adhesePerSite = [];
        foreach ($adheseRows as $row) {
            $siteId = self::siteForDomain($row['site']);
            if (! $siteId) continue;
            $k = Reporting::dateKey($row['date']);
            $adhesePerSite[$siteId][$k] ??= ['date' => $row['date'], 'revenue' => 0.0];
            $adhesePerSite[$siteId][$k]['revenue'] += $row['revenue'];
        }

        // Clear optional files at the start — written back only if this run has data.
        foreach (['Outbrain', 'PreferredDeals'] as $name) {
            foreach (['.csv', '.xlsx'] as $e) {
                $p = $uploadsDir . '/' . $name . $e;
                if (is_file($p)) unlink($p);
            }
        }

        $thisMonth = $today->format('Y-m');
        $sevenDaysAgoKey = $today->subDays(7)->format('Y-m-d');

        $outbrainHasData = false;
        $preferredHasData = false;

        foreach (array_keys(Reporting::SITES) as $siteId) {
            $parsed = [];
            try {
                if (isset($pathByType['seedtag'])) $parsed['seedtag'] = Extractors::seedtag($pathByType['seedtag'], $siteId);
                if (isset($pathByType['teads'])) $parsed['teads'] = Extractors::teads($pathByType['teads'], $siteId);
                if (isset($pathByType['showheroes'])) $parsed['showheroes'] = Extractors::showheroes($pathByType['showheroes'], $siteId);
                if (isset($pathByType['gam'])) $parsed['gam'] = Extractors::gam($pathByType['gam'], $siteId);
                if (isset($pathByType['adform'])) $parsed['adform'] = Extractors::adform($pathByType['adform'], $siteId);
                if (isset($pathByType['ogury'])) $parsed['ogury'] = Extractors::ogury($pathByType['ogury'], $siteId, $oguryRate);
                if (isset($adhesePerSite[$siteId])) $parsed['adhese'] = array_values($adhesePerSite[$siteId]);

                if ($siteId === 'f1maximaal') {
                    if (isset($pathByType['analytics'])) $parsed['analytics'] = Extractors::analytics(file_get_contents($pathByType['analytics']));
                    if (isset($pathByType['gam_f1m'])) $parsed['gam_f1m'] = Extractors::gamF1m($pathByType['gam_f1m']);
                    if (isset($pathByType['outbrain'])) {
                        $parsed['outbrain'] = Extractors::outbrain(file_get_contents($pathByType['outbrain']));
                        $outbrainHasData = self::anyImpressions($parsed['outbrain']);
                    }
                    if (isset($pathByType['preferreddeals'])) {
                        $parsed['preferreddeals'] = Extractors::preferredDeals($pathByType['preferreddeals']);
                        $preferredHasData = self::anyImpressions($parsed['preferreddeals']);
                    }
                    if (isset($pathByType['impressions_f1'])) $parsed['impressions_f1'] = Extractors::impressionsF1($pathByType['impressions_f1']);
                }

                // Strip past-month rows before merging (current month + last 7 days).
                foreach ($parsed as $key => $arr) {
                    if (! is_array($arr)) continue;
                    $parsed[$key] = array_values(array_filter($arr, function ($r) use ($thisMonth, $sevenDaysAgoKey) {
                        $dk = isset($r['date']) ? Reporting::dateKey($r['date']) : null;
                        return $dk && (str_starts_with($dk, $thisMonth) || $dk >= $sevenDaysAgoKey);
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

        foreach ([
            ['type' => 'outbrain', 'name' => 'Outbrain', 'has' => $outbrainHasData, 'defExt' => '.csv'],
            ['type' => 'preferreddeals', 'name' => 'PreferredDeals', 'has' => $preferredHasData, 'defExt' => '.xlsx'],
        ] as $c) {
            if (! isset($pathByType[$c['type']]) || ! $c['has']) continue;
            $ext = '.' . (pathinfo($origByType[$c['type']] ?? '', PATHINFO_EXTENSION) ?: ltrim($c['defExt'], '.'));
            copy($pathByType[$c['type']], $uploadsDir . '/' . $c['name'] . $ext);
        }

        file_put_contents($uploadsDir . '/Analytics.csv', CsvGenerator::analytics($store));
        file_put_contents($uploadsDir . '/Adhese f1.csv', CsvGenerator::adhese($store, 'f1maximaal'));
        foreach (['topgear' => 'tg', 'festileaks' => 'fl'] as $sid => $label) {
            $csv = CsvGenerator::adhese($store, $sid);
            if (count(explode("\n", $csv)) > 1) {
                file_put_contents($uploadsDir . "/Adhese {$label}.csv", $csv);
            }
        }

        return [
            'fileTypes' => $fileTypes,
            'store' => $store,
            'analyticsCSV' => CsvGenerator::analytics($store),
            'adheseCSV' => CsvGenerator::adhese($store, 'f1maximaal'),
        ];
    }

    private static function siteForDomain(string $domain): ?string
    {
        foreach (Reporting::SITES as $id => $cfg) {
            if ($cfg['domain'] === $domain) return $id;
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
