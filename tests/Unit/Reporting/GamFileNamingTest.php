<?php

use App\Services\Reporting\Reporting;

/**
 * GAM changed its export names from "Copy of …" to "GAM - …" / no prefix.
 * Both the old and the current names must still be recognised.
 */
it('recognises GAM exports under the old and the current naming', function (string $name, string $type) {
    expect(Reporting::detectFileType($name))->toBe($type);
})->with([
    'general, old' => ['Copy of General Data Download for Publishers - 5 publishers (Sep 15, 2026 - Oct 4, 2026).xlsx', 'gam'],
    'general, current' => ['General Data Download for Publishers - 5 publishers (Sep 20, 2026 - Oct 5, 2026).xlsx', 'gam'],
    'F1, old' => ['Copy of F1Maximaal (Sep 15, 2026 - Oct 4, 2026).xlsx', 'gam_f1m'],
    'F1, current' => ['GAM - F1Maximaal (Sep 15, 2026 - Oct 5, 2026).xlsx', 'gam_f1m'],
    'Topgear, current' => ['GAM - Topgear (Sep 20, 2026 - Oct 5, 2026).xlsx', 'gam_f1m'],
    'JFK, current' => ['GAM - JFK (Sep 20, 2026 - Oct 5, 2026).xlsx', 'gam_f1m'],
    'preferred deals' => ['Automated Report - GAM - Preferred_Deals f1max (Sep 20, 2026 - Oct 5, 2026).xlsx', 'preferreddeals'],
]);
