<?php

namespace App\Services\ReportCheck\Parsers;

use App\Services\ReportCheck\Helpers\Money;
use App\Services\ReportCheck\Helpers\XlsxReader;
use App\Services\ReportCheck\PublisherConfig;
use App\Services\ReportCheck\RevenueRow;
use App\Services\ReportCheck\SectionClassifier;
use DateTime;

class GamParser implements Contract
{
    public function sourceKey(): string { return 'gam'; }

    public function parse(string $path, PublisherConfig $publisher): array
    {
        $rootPrefix = $publisher->rule('gam_root_prefix');
        $rows = [];
        foreach (XlsxReader::rows($path, 'General Data Download for Publi', 1) as $r) {
            $unit = $r['Ad unit (all levels)'] ?? null;
            if (!is_string($unit) || stripos($unit, $rootPrefix) === false) continue;
            // Skip the parent-only row (which lacks the » hierarchy separator).
            if (!str_contains($unit, '»')) continue;

            $leaf = trim((string) substr($unit, strrpos($unit, '»') + 1));
            $d = DateTime::createFromFormat('Y-m-d', (string) ($r['Date'] ?? ''));
            if (!$d) continue;

            $revenue = Money::parse($r['AdSense revenue'] ?? null) + Money::parse($r['Ad Exchange revenue'] ?? null);

            $rows[] = new RevenueRow(
                date: $d->format('Y-m-d'),
                partner: 'GAM',
                section: SectionClassifier::fromPlacementName($leaf),
                revenue: $revenue,
                currency: 'EUR',
            );
        }
        return $rows;
    }
}
