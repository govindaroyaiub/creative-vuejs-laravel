<?php

namespace App\Services\ReportCheck\Parsers;

use App\Services\ReportCheck\AnalyticsRow;
use App\Services\ReportCheck\Helpers\CsvReader;
use App\Services\ReportCheck\PublisherConfig;
use DateTime;

/**
 * GA4 export. Doesn't fit the revenue Contract — emits AnalyticsRow
 * (pageviews / ad requests / impressions sold) for the Trend sheet.
 *
 * The export starts with `#`-prefixed comment lines; the real header
 * (`Content group, Date, Views, ...`) comes after them.
 */
class AnalyticsParser
{
    public function sourceKey(): string { return 'analytics'; }

    /** @return array<int,AnalyticsRow> */
    public function parse(string $path, PublisherConfig $publisher): array
    {
        $group = strtolower($publisher->rule('analytics_content_group'));
        $rows = [];
        foreach (CsvReader::rows($path, headerRow: 1, stripHashComments: true) as $r) {
            if (strtolower((string) ($r['Content group'] ?? '')) !== $group) continue;

            $d = DateTime::createFromFormat('Ymd', (string) ($r['Date'] ?? ''));
            if (!$d) continue;

            $rows[] = new AnalyticsRow(
                date: $d->format('Y-m-d'),
                pageviews: (int) ($r['Views'] ?? 0),
                ad_requests: (int) ($r['Total Ad Requests'] ?? 0),
                impressions_sold: (int) ($r['Impressions Sold'] ?? 0),
            );
        }
        return $rows;
    }
}
