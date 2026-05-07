<?php

namespace App\Services\ReportCheck\Parsers;

use App\Services\ReportCheck\Helpers\Money;
use App\Services\ReportCheck\Helpers\XlsxReader;
use App\Services\ReportCheck\PublisherConfig;
use App\Services\ReportCheck\RevenueRow;
use App\Services\ReportCheck\SectionClassifier;
use DateTime;

/**
 * Ogury reports in USD — emitted RevenueRow keeps `currency = USD`,
 * the validator applies the auto-fitted FX rate downstream.
 */
class OguryParser implements Contract
{
    public function sourceKey(): string { return 'ogury'; }

    private const FORMAT_TO_SECTION = [
        'standard_banners' => SectionClassifier::DISPLAY,
        'footer_ad'        => SectionClassifier::STICKY,
        'in-article'       => SectionClassifier::INARTICLE,
    ];

    public function parse(string $path, PublisherConfig $publisher): array
    {
        $asset = strtolower($publisher->rule('ogury_asset'));
        $rows = [];
        foreach (XlsxReader::rows($path, 'Statistics 1', 1) as $r) {
            if (strtolower((string) ($r['Asset'] ?? '')) !== $asset) continue;

            $fmt = strtolower((string) ($r['Format Key'] ?? ''));
            $section = self::FORMAT_TO_SECTION[$fmt] ?? null;
            if ($section === null) continue;

            $date = $r['Date'] ?? null;
            $key = is_string($date) ? substr($date, 0, 10) : null;
            if ($key === null) continue;
            $d = DateTime::createFromFormat('Y-m-d', $key);
            if (!$d) continue;

            $rows[] = new RevenueRow(
                date: $d->format('Y-m-d'),
                partner: 'Ogury',
                section: $section,
                revenue: Money::parse($r['Revenues'] ?? null),
                currency: 'USD',
            );
        }
        return $rows;
    }
}
