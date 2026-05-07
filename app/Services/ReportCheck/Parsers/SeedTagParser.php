<?php

namespace App\Services\ReportCheck\Parsers;

use App\Services\ReportCheck\Helpers\Money;
use App\Services\ReportCheck\Helpers\XlsxReader;
use App\Services\ReportCheck\PublisherConfig;
use App\Services\ReportCheck\RevenueRow;
use App\Services\ReportCheck\SectionClassifier;
use DateTime;

class SeedTagParser implements Contract
{
    public function sourceKey(): string { return 'seedtag'; }

    private const PLACEMENT_TO_SECTION = [
        'inbanner'  => SectionClassifier::DISPLAY,
        'inarticle' => SectionClassifier::INARTICLE,
        'inscreen'  => SectionClassifier::INTERSCROLLER,
    ];

    public function parse(string $path, PublisherConfig $publisher): array
    {
        $pub = strtolower($publisher->rule('seedtag_publisher'));
        $rows = [];
        foreach (XlsxReader::rows($path, 'Revenue', 1) as $r) {
            if (strtolower((string) ($r['Publisher'] ?? '')) !== $pub) continue;

            $section = self::PLACEMENT_TO_SECTION[strtolower((string) ($r['Placement'] ?? ''))] ?? null;
            if ($section === null) continue;

            $date = $r['Date'] ?? null;
            $key = is_string($date) ? substr($date, 0, 10) : null;
            if ($key === null) continue;
            $d = DateTime::createFromFormat('Y-m-d', $key);
            if (!$d) continue;

            $rows[] = new RevenueRow(
                date: $d->format('Y-m-d'),
                partner: 'Seedtag',
                section: $section,
                revenue: Money::parse($r['Revenue'] ?? null),
                currency: 'EUR',
            );
        }
        return $rows;
    }
}
