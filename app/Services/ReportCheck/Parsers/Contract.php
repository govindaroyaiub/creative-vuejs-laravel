<?php

namespace App\Services\ReportCheck\Parsers;

use App\Services\ReportCheck\PublisherConfig;
use App\Services\ReportCheck\RevenueRow;

interface Contract
{
    /** @return array<int,RevenueRow> */
    public function parse(string $path, PublisherConfig $publisher): array;

    /** Source slug used in report_check_files.source_key. */
    public function sourceKey(): string;
}
