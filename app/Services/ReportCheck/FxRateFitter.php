<?php

namespace App\Services\ReportCheck;

/**
 * Auto-fit a single USD→EUR rate per check by dividing the outcome's
 * total Ogury EUR by the parsed raw's total Ogury USD.
 *
 * Why a single rate: in practice the manual report generator applies one
 * (or close-to-one) rate per period; per-row variance in our sample is
 * <1%, dominated by rounding of $0.07-style values.
 */
class FxRateFitter
{
    public const SANE_MIN = 0.70;
    public const SANE_MAX = 1.10;

    /**
     * @param array<int,RevenueRow> $rows         All parsed revenue rows (USD entries are Ogury's)
     * @param array<int,array>      $outcomeCells Outcome reader's `cells` array
     * @return array{rate: ?float, total_usd: float, total_eur: float, outlier: bool, message: ?string}
     */
    public function fit(array $rows, array $outcomeCells): array
    {
        $totalUsd = 0.0;
        foreach ($rows as $r) {
            if ($r->currency === 'USD') $totalUsd += $r->revenue;
        }

        $totalEur = 0.0;
        foreach ($outcomeCells as $c) {
            if (($c['role'] ?? null) !== 'summary.value') continue;
            if (($c['attrs']['partner'] ?? null) !== 'Ogury') continue;
            $totalEur += (float) ($c['value'] ?? 0);
        }

        if ($totalUsd <= 0.0) {
            // No Ogury USD in raw — either the source is missing or the period really had zero Ogury revenue.
            return [
                'rate'      => null,
                'total_usd' => 0.0,
                'total_eur' => $totalEur,
                'outlier'   => false,
                'message'   => $totalEur > 0.01
                    ? 'Outcome has Ogury values but raw has no Ogury USD revenue — cannot fit FX rate'
                    : null,
            ];
        }

        $rate = $totalEur / $totalUsd;
        $outlier = $rate < self::SANE_MIN || $rate > self::SANE_MAX;

        return [
            'rate'      => $rate,
            'total_usd' => $totalUsd,
            'total_eur' => $totalEur,
            'outlier'   => $outlier,
            'message'   => $outlier
                ? sprintf('Fitted USD→EUR rate %.4f is outside the sane band [%.2f, %.2f] — Ogury cells may be wrong', $rate, self::SANE_MIN, self::SANE_MAX)
                : null,
        ];
    }
}
