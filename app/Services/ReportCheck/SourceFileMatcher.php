<?php

namespace App\Services\ReportCheck;

class SourceFileMatcher
{
    public const SOURCE_KEYS = [
        'adform', 'gam', 'ogury', 'seedtag', 'showheroes', 'teads',
        'adhese', 'outbrain', 'analytics', 'outcome',
    ];

    /**
     * Map a filename to a source key (case-insensitive). Returns null
     * for unrecognized files. The user's expected names are documented
     * in `report-files/raw files (before)/` — Outcome name varies by
     * date so we match it as `* Revenue Report *.xlsx`.
     */
    public static function detect(string $filename): ?string
    {
        $name = strtolower(trim($filename));

        // Exact-name sources.
        $exact = [
            'adform.xlsx'      => 'adform',
            'gam.xlsx'         => 'gam',
            'ogury.xlsx'       => 'ogury',
            'seedtag.xlsx'     => 'seedtag',
            'showheroes.xlsx'  => 'showheroes',
            'teads.xlsx'       => 'teads',
            'outbrain.csv'     => 'outbrain',
            'analytics.csv'    => 'analytics',
        ];
        if (isset($exact[$name])) return $exact[$name];

        // Adhese: filename includes a publisher suffix like `Adhese f1.csv`.
        if (preg_match('/^adhese[\w \-]*\.csv$/', $name)) return 'adhese';

        // Outcome: any xlsx with "revenue report" in the name.
        if (str_contains($name, 'revenue report') && str_ends_with($name, '.xlsx')) return 'outcome';

        return null;
    }

    /** @return list<string> source keys that no uploaded file matched */
    public static function missing(array $detected): array
    {
        return array_values(array_diff(self::SOURCE_KEYS, array_keys($detected)));
    }
}
