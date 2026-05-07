<?php

namespace App\Services\ReportCheck\Parsers;

use App\Services\ReportCheck\Helpers\Money;
use App\Services\ReportCheck\Helpers\XlsxReader;
use App\Services\ReportCheck\PublisherConfig;
use App\Services\ReportCheck\RevenueRow;
use App\Services\ReportCheck\SectionClassifier;
use DateTime;

class TeadsParser implements Contract
{
    public function sourceKey(): string { return 'teads'; }

    public function parse(string $path, PublisherConfig $publisher): array
    {
        $site = strtolower($publisher->rule('teads_site'));
        $rows = [];
        foreach (XlsxReader::rows($path, 'default', 10) as $r) {
            if (strtolower((string) ($r['Websites & Apps'] ?? '')) !== $site) continue;

            $d = DateTime::createFromFormat('m/d/Y', (string) ($r['Day'] ?? ''));
            if (!$d) continue;

            $rows[] = new RevenueRow(
                date: $d->format('Y-m-d'),
                partner: 'Teads',
                section: SectionClassifier::INARTICLE,
                revenue: Money::parse($r['Estimated Earnings in EUR'] ?? null),
                currency: 'EUR',
            );
        }
        return $rows;
    }
}
