<?php

namespace App\Services\ReportCheck;

class AnalyticsRow
{
    public function __construct(
        public readonly string $date,        // Y-m-d
        public readonly int $pageviews,
        public readonly int $ad_requests,
        public readonly int $impressions_sold,
    ) {}
}
