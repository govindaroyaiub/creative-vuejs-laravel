<?php

namespace App\Services\ReportCheck\Parsers;

use App\Services\ReportCheck\Helpers\CsvReader;
use App\Services\ReportCheck\Helpers\Money;
use App\Services\ReportCheck\PublisherConfig;
use App\Services\ReportCheck\RevenueRow;
use App\Services\ReportCheck\SectionClassifier;
use DateTime;

class OutbrainParser implements Contract
{
    public function sourceKey(): string { return 'outbrain'; }

    public function parse(string $path, PublisherConfig $publisher): array
    {
        $needle = strtolower($publisher->rule('outbrain_publisher'));
        $rows = [];
        foreach (CsvReader::rows($path) as $r) {
            $pub = strtolower((string) ($r['Publisher'] ?? ''));
            if ($pub === '' || !str_contains($pub, $needle)) continue;

            $d = DateTime::createFromFormat('m/d/Y', (string) ($r['Day'] ?? ''));
            if (!$d) continue;

            $rows[] = new RevenueRow(
                date: $d->format('Y-m-d'),
                partner: 'Outbrain',
                section: SectionClassifier::DISPLAY,
                revenue: Money::parse($r['Revenue'] ?? null),
                currency: 'EUR',
            );
        }
        return $rows;
    }
}
