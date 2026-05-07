<?php

namespace App\Services\ReportCheck;

/**
 * Map an Adform Placement / GAM ad-unit-leaf-name to a section.
 * Order matters — `interscroller` and `sticky` checked before `outstream`
 * so that placements containing both keywords land in the more specific
 * bucket (e.g. `sidebar_sticky_vertical_delta` → sticky, not display).
 */
class SectionClassifier
{
    public const DISPLAY = 'display';
    public const STICKY = 'sticky';
    public const INARTICLE = 'inarticle';
    public const INTERSCROLLER = 'interscroller';

    public static function fromPlacementName(string $name): string
    {
        $n = strtolower($name);
        if (str_contains($n, 'interscroller')) return self::INTERSCROLLER;
        if (str_contains($n, 'sticky'))        return self::STICKY;
        if (str_contains($n, 'outstream'))     return self::INARTICLE;
        return self::DISPLAY;
    }
}
