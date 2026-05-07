<?php

namespace App\Services\ReportCheck;

class RevenueRow
{
    public function __construct(
        public readonly string $date,        // Y-m-d
        public readonly string $partner,     // Adform, GAM, Ogury, ...
        public readonly string $section,     // display, sticky, inarticle, interscroller
        public readonly float $revenue,      // in `currency`
        public readonly string $currency,    // EUR or USD
    ) {}
}
