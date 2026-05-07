<?php

namespace App\Services\ReportCheck\Parsers;

use App\Services\ReportCheck\Helpers\Money;
use App\Services\ReportCheck\Helpers\XlsxReader;
use App\Services\ReportCheck\PublisherConfig;
use App\Services\ReportCheck\RevenueRow;
use App\Services\ReportCheck\SectionClassifier;
use DateTime;

class AdformParser implements Contract
{
    public function sourceKey(): string { return 'adform'; }

    public function parse(string $path, PublisherConfig $publisher): array
    {
        $prefix = $publisher->rule('adform_placement_prefix');
        $rows = [];
        foreach (XlsxReader::rows($path, 'Sheet', 3) as $r) {
            $placement = $r['Placement'] ?? null;
            if (!is_string($placement) || !str_starts_with($placement, $prefix)) continue;

            $d = DateTime::createFromFormat('d-m-Y', (string) ($r['Date'] ?? ''));
            if (!$d) continue;

            $rows[] = new RevenueRow(
                date: $d->format('Y-m-d'),
                partner: 'Adform',
                section: SectionClassifier::fromPlacementName($placement),
                revenue: Money::parse($r['Revenue (Net)'] ?? null),
                currency: 'EUR',
            );
        }
        return $rows;
    }
}
