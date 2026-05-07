<?php

namespace App\Services\ReportCheck\Parsers;

use App\Services\ReportCheck\Helpers\Money;
use App\Services\ReportCheck\Helpers\XlsxReader;
use App\Services\ReportCheck\PublisherConfig;
use App\Services\ReportCheck\RevenueRow;
use App\Services\ReportCheck\SectionClassifier;
use DateTime;

class ShowHeroesParser implements Contract
{
    public function sourceKey(): string { return 'showheroes'; }

    public function parse(string $path, PublisherConfig $publisher): array
    {
        $site = strtolower($publisher->rule('showheroes_site'));
        $rows = [];
        foreach (XlsxReader::rows($path, 'Sheet1', 1) as $r) {
            // Row 2 is a "Totals" summary row — skip rows that don't carry a real date.
            $rawDate = $r['Date and Time'] ?? null;
            if (!is_string($rawDate) || str_contains(strtolower($rawDate), 'total')) continue;

            if (strtolower((string) ($r['Site'] ?? '')) !== $site) continue;

            // Date may arrive as ISO string `2026-05-01T00:00:00`.
            $d = DateTime::createFromFormat('Y-m-d\TH:i:s', substr($rawDate, 0, 19))
              ?: DateTime::createFromFormat('Y-m-d', substr($rawDate, 0, 10));
            if (!$d) continue;

            $rows[] = new RevenueRow(
                date: $d->format('Y-m-d'),
                partner: 'Showheroes',
                section: SectionClassifier::INARTICLE,
                revenue: Money::parse($r['Premium Revenue'] ?? null),
                currency: 'EUR',
            );
        }
        return $rows;
    }
}
