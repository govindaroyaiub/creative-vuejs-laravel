<?php

namespace App\Services\ReportCheck\Parsers;

use App\Services\ReportCheck\Helpers\CsvReader;
use App\Services\ReportCheck\Helpers\Money;
use App\Services\ReportCheck\PublisherConfig;
use App\Services\ReportCheck\RevenueRow;
use App\Services\ReportCheck\SectionClassifier;
use DateTime;

class AdheseParser implements Contract
{
    public function sourceKey(): string { return 'adhese'; }

    public function parse(string $path, PublisherConfig $publisher): array
    {
        $site = strtolower($publisher->rule('adhese_site'));
        $rows = [];
        foreach (CsvReader::rows($path) as $r) {
            if (strtolower((string) ($r['site'] ?? '')) !== $site) continue;

            $d = DateTime::createFromFormat('j-M-y', (string) ($r['date'] ?? ''));
            if (!$d) continue;

            $rows[] = new RevenueRow(
                date: $d->format('Y-m-d'),
                partner: 'Adhese',
                section: SectionClassifier::DISPLAY,
                revenue: Money::parse($r['Paid Revenue'] ?? null),
                currency: 'EUR',
            );
        }
        return $rows;
    }
}
