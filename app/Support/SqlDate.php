<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Driver-aware SQL date expressions.
 *
 * The controllers used `DATE_FORMAT()` and `MONTH()` directly, which only
 * exist on MySQL. Production runs MySQL so those pages worked, but the test
 * suite runs on SQLite (`phpunit.xml`) and every request touching them died
 * with `no such function: MONTH`. That made the dashboard and all four search
 * endpoints untestable.
 *
 * These helpers emit the same result on both drivers so the queries stay in
 * SQL (searching and grouping cannot move into PHP without loading whole
 * tables) while remaining testable.
 */
class SqlDate
{
    private const MONTH_NAMES = [
        'January', 'February', 'March', 'April', 'May', 'June',
        'July', 'August', 'September', 'October', 'November', 'December',
    ];

    /**
     * The month as an integer 1-12, for grouping.
     */
    public static function month(string $column): string
    {
        return match (self::driver()) {
            'sqlite' => "CAST(strftime('%m', {$column}) AS INTEGER)",
            'pgsql' => "EXTRACT(MONTH FROM {$column})",
            default => "MONTH({$column})",
        };
    }

    /**
     * A long human date — "07 August 2026" — matching MySQL's
     * `DATE_FORMAT(col, '%d %M %Y')`, which is what the search boxes match
     * against so a user can type "August" or "21 January 2026".
     */
    public static function longDate(string $column): string
    {
        return match (self::driver()) {
            'sqlite' => self::sqliteLongDate($column),
            'pgsql' => "TO_CHAR({$column}, 'DD FMMonth YYYY')",
            default => "DATE_FORMAT({$column}, '%d %M %Y')",
        };
    }

    /**
     * SQLite has no month-name token, so the name is spelled out with a CASE
     * over `strftime('%m')`.
     */
    private static function sqliteLongDate(string $column): string
    {
        $cases = '';
        foreach (self::MONTH_NAMES as $i => $name) {
            $number = str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT);
            $cases .= " WHEN '{$number}' THEN '{$name}'";
        }

        return "(strftime('%d ', {$column}) || CASE strftime('%m', {$column}){$cases} END || strftime(' %Y', {$column}))";
    }

    private static function driver(): string
    {
        return DB::connection()->getDriverName();
    }
}
