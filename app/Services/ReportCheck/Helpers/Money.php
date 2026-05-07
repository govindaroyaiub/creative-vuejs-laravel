<?php

namespace App\Services\ReportCheck\Helpers;

class Money
{
    /**
     * Tolerantly parse a money-shaped value into a float.
     *
     * Handles: numeric scalars, `€0.10 ` with trailing space, `$0.07`,
     * `1,234.56` (US thousands), bare strings. Returns 0.0 for null
     * or empty strings.
     */
    public static function parse(mixed $v): float
    {
        if ($v === null || $v === '') return 0.0;
        if (is_numeric($v)) return (float) $v;

        $s = (string) $v;
        // Strip currency markers + non-breaking & regular spaces.
        $s = str_replace(['€', '$', "\xC2\xA0", ' '], '', $s);
        // US thousands separator — strip all commas (we never see EU decimal-comma in our inputs).
        $s = str_replace(',', '', $s);

        return (float) $s;
    }
}
