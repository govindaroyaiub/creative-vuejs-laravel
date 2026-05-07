<?php

namespace App\Services\ReportCheck;

use App\Models\ReportCheck;
use App\Models\ReportCheckIssue;

/**
 * Compares recomputed truth (from raw parsers) against the produced
 * outcome workbook (from OutcomeReader) and emits a list of issues.
 *
 * Returns a payload with:
 *   - issues:   array of issue rows ready for ReportCheckIssue::create
 *   - revenues: flat truth rows ready for ReportCheckRevenue::create
 *   - status:   pass / fail_minor / fail_major / error
 *   - totals:   denormalized roll-ups for fast list-view rendering
 */
class ReportValidator
{
    // EUR comparison thresholds.
    private const TOL_EUR_MINOR = 0.10;
    private const TOL_EUR_MAJOR = 0.50;

    // Ogury rows go through FX rounding; widen.
    private const TOL_OGURY_MINOR = 0.20;
    private const TOL_OGURY_MAJOR = 1.00;

    // Percent metrics like Sold Trend (stored as fractions).
    private const TOL_PCT_MINOR = 0.01;  // 1 percentage point
    private const TOL_PCT_MAJOR = 0.05;  // 5 pp

    // Unitless ratios (RPM, Ad request / pageview).
    private const TOL_RATIO_MINOR = 0.05;
    private const TOL_RATIO_MAJOR = 0.50;

    /**
     * The bottom `Sticky` / `Inarticle` / `Display` / `Interscroller`
     * sheets show in-house programmatic revenue only — affiliate
     * (Adhese) and native (Outbrain) partners are excluded by design.
     */
    private const PER_SECTION_SHEET_EXCLUDED_PARTNERS = ['Adhese', 'Outbrain'];

    /**
     * Which raw source file each partner's revenue comes from. Used in
     * issue explanations so the UI can point the user back to the right
     * file when a value is wrong.
     */
    private const PARTNER_SOURCE_FILE = [
        'Adform'     => 'Adform.xlsx',
        'GAM'        => 'GAM.xlsx',
        'Ogury'      => 'Ogury.xlsx',
        'Seedtag'    => 'SeedTag.xlsx',
        'Showheroes' => 'Showheroes.xlsx',
        'Teads'      => 'Teads.xlsx',
        'Adhese'     => 'Adhese f1.csv',
        'Outbrain'   => 'Outbrain.csv',
    ];

    /**
     * @param array<int,RevenueRow>   $revenueRows
     * @param array<int,AnalyticsRow> $analyticsRows
     * @param array{cells:array,errors:array,period_label:?string} $outcomeSnapshot
     * @param array{rate:?float,total_usd:float,total_eur:float,outlier:bool,message:?string} $fxFit
     * @return array{issues:array,revenues:array,status:string,totals:array}
     */
    public function validate(
        array $revenueRows,
        array $analyticsRows,
        array $outcomeSnapshot,
        array $fxFit,
    ): array {
        $issues = [];

        // --- Truth — collapse parsed revenue into [block][date][partner] => EUR.
        [$truth, $detail, $revenues] = $this->buildTruth($revenueRows, $fxFit['rate']);

        // --- Index outcome cells for fast lookup by role + attrs.
        $idx = $this->indexOutcome($outcomeSnapshot['cells']);

        // --- Structural errors from the reader become missing-source issues.
        foreach ($outcomeSnapshot['errors'] ?? [] as $msg) {
            $issues[] = $this->issue('Summary', null, ReportCheckIssue::KIND_MISSING_SOURCE, ReportCheckIssue::SEVERITY_MAJOR, null, null, null, $msg, [
                'why'  => $msg,
                'hint' => 'The produced workbook is missing this sheet or block. Re-export the report from the generator.',
            ]);
        }

        // --- FX outlier — single rate-level issue.
        if ($fxFit['outlier']) {
            $issues[] = $this->issue('Summary', null, ReportCheckIssue::KIND_RATE_OUTLIER, ReportCheckIssue::SEVERITY_MAJOR, null, $fxFit['rate'], null, $fxFit['message'], $this->explainRateOutlier($fxFit));
        } elseif ($fxFit['message'] !== null) {
            $issues[] = $this->issue('Summary', null, ReportCheckIssue::KIND_RATE_OUTLIER, ReportCheckIssue::SEVERITY_MINOR, null, $fxFit['rate'], null, $fxFit['message'], $this->explainRateOutlier($fxFit));
        }

        // --- Phase A: value cells vs truth.
        $this->checkSummaryValues($idx, $truth, $detail, $fxFit['rate'], $issues);

        // --- Phase B: outcome's internal arithmetic.
        $this->checkInternalArithmetic($idx, $issues);

        // --- Phase C: cross-sheet consistency.
        $this->checkCrossSheet($idx, $truth, $issues);

        // --- Phase D: Trend analytics cells vs analytics parser.
        $analyticsByDate = [];
        foreach ($analyticsRows as $a) $analyticsByDate[$a->date] = $a;
        $this->checkTrendAnalytics($idx, $analyticsByDate, $issues);

        // --- Phase E: Trend derived cells.
        $this->checkTrendDerived($idx, $analyticsByDate, $issues);

        $status = $this->verdict($issues);
        $totals = $this->totals($truth);

        return compact('issues', 'revenues', 'status', 'totals');
    }

    // ============================ TRUTH + INDEX ============================

    /**
     * @param array<int,RevenueRow> $rows
     * @return array{0:array<string,array<string,array<string,float>>>,1:array<string,array<string,array<string,array>>>,2:array<int,array>}
     *  - truth:    [block][date][partner] = eur (for fast comparison)
     *  - detail:   [block][date][partner] = ['eur','local','currency','row_count'] (for issue explanations)
     *  - revenues: flat rows for report_check_revenues
     */
    private function buildTruth(array $rows, ?float $fxRate): array
    {
        $truth = [];
        $detail = [];
        $revenues = [];

        foreach ($rows as $r) {
            $eur = $r->currency === 'USD'
                ? ($fxRate !== null ? $r->revenue * $fxRate : 0.0)
                : $r->revenue;

            $truth[$r->section][$r->date][$r->partner]
                = ($truth[$r->section][$r->date][$r->partner] ?? 0.0) + $eur;

            $d = &$detail[$r->section][$r->date][$r->partner];
            $d ??= ['eur' => 0.0, 'local' => 0.0, 'currency' => $r->currency, 'row_count' => 0];
            $d['eur']       += $eur;
            $d['local']     += $r->revenue;
            $d['row_count']++;
            unset($d);
        }

        foreach ($detail as $section => $byD) {
            foreach ($byD as $date => $byP) {
                foreach ($byP as $partner => $info) {
                    $revenues[] = [
                        'date'           => $date,
                        'partner'        => $partner,
                        'section'        => $section,
                        'revenue_eur'    => round($info['eur'], 4),
                        'revenue_local'  => $info['currency'] === 'USD' ? round($info['local'], 4) : null,
                        'currency_local' => $info['currency'] === 'USD' ? 'USD' : null,
                    ];
                }
            }
        }

        return [$truth, $detail, $revenues];
    }

    /**
     * Index outcome cells for fast lookup.
     *
     * Returned shape (only keys we actually use):
     *   'summary.value'                    => [block][date][partner] => cell
     *   'summary.row_total'                => [block][date]          => cell
     *   'summary.col_total'                => [block][partner]       => cell
     *   'summary.section_total'            => [block]                => cell
     *   'summary.daily_total_row'          => [date][block]          => cell
     *   'summary.daily_total_row_total'    => [date]                 => cell
     *   'summary.daily_total_col_total'    => [block]                => cell
     *   'summary.daily_grand'              => cell
     *   'summary.section_totals'           => [block][partner]       => cell
     *   'summary.section_totals_row_total' => [block]                => cell
     *   'summary.grand_total'              => cell
     *   'demand_partners.value'            => [date][partner]        => cell
     *   'demand_partners.total'            => [date]                 => cell
     *   'trend.metric'                     => [date][metric]         => cell
     *   'section_sheet.value'              => [block][date]          => cell
     */
    private function indexOutcome(array $cells): array
    {
        $idx = [];
        foreach ($cells as $c) {
            $role = $c['role'];
            $a = $c['attrs'];
            switch ($role) {
                case 'summary.value':
                    $idx[$role][$a['block']][$a['date']][$a['partner']] = $c; break;
                case 'summary.row_total':
                    $idx[$role][$a['block']][$a['date']] = $c; break;
                case 'summary.col_total':
                    $idx[$role][$a['block']][$a['partner']] = $c; break;
                case 'summary.section_total':
                    $idx[$role][$a['block']] = $c; break;
                case 'summary.daily_total_row':
                    $idx[$role][$a['date']][$a['block']] = $c; break;
                case 'summary.daily_total_row_total':
                    $idx[$role][$a['date']] = $c; break;
                case 'summary.daily_total_col_total':
                    $idx[$role][$a['block']] = $c; break;
                case 'summary.daily_grand':
                case 'summary.grand_total':
                    $idx[$role] = $c; break;
                case 'summary.section_totals':
                    $idx[$role][$a['block']][$a['partner']] = $c; break;
                case 'summary.section_totals_row_total':
                    $idx[$role][$a['block']] = $c; break;
                case 'demand_partners.value':
                    $idx[$role][$a['date']][$a['partner']] = $c; break;
                case 'demand_partners.total':
                    $idx[$role][$a['date']] = $c; break;
                case 'trend.metric':
                    $idx[$role][$a['date']][$a['metric']] = $c; break;
                case 'section_sheet.value':
                    $idx[$role][$a['block']][$a['date']] = $c; break;
            }
        }
        return $idx;
    }

    // ============================ PHASE A ============================

    private function checkSummaryValues(array $idx, array $truth, array $detail, ?float $fxRate, array &$issues): void
    {
        foreach ($idx['summary.value'] ?? [] as $block => $byDate) {
            foreach ($byDate as $date => $byPartner) {
                foreach ($byPartner as $partner => $cell) {
                    $expected = $truth[$block][$date][$partner] ?? 0.0;
                    $found = (float) $cell['value'];
                    $isOgury = $partner === 'Ogury';
                    [$minor, $major] = $isOgury
                        ? [self::TOL_OGURY_MINOR, self::TOL_OGURY_MAJOR]
                        : [self::TOL_EUR_MINOR, self::TOL_EUR_MAJOR];
                    $this->compare(
                        $issues, $cell, ReportCheckIssue::KIND_VALUE,
                        $expected, $found, $minor, $major,
                        sprintf('%s %s %s', ucfirst($block), $date, $partner),
                        $this->explainSummaryValue($block, $date, $partner, $detail[$block][$date][$partner] ?? null, $fxRate)
                    );
                }
            }
        }
    }

    // ============================ PHASE B ============================

    private function checkInternalArithmetic(array $idx, array &$issues): void
    {
        // summary.row_total = sum of summary.value for (block, date).
        foreach ($idx['summary.row_total'] ?? [] as $block => $byDate) {
            foreach ($byDate as $date => $cell) {
                $sum = 0.0; $components = [];
                foreach ($idx['summary.value'][$block][$date] ?? [] as $partner => $vc) {
                    $v = (float) $vc['value'];
                    $sum += $v;
                    $components[] = ['label' => $partner, 'value' => round($v, 2), 'ref' => $vc['cell_ref']];
                }
                $this->compare($issues, $cell, ReportCheckIssue::KIND_ROW_TOTAL,
                    $sum, (float)$cell['value'], self::TOL_EUR_MINOR, self::TOL_EUR_MAJOR,
                    "Row total {$block} {$date}",
                    $this->explainInternalSum("Row total = sum of partner cells in this row of the {$block} block.", $components, 'A formula in the Total cell may have been overwritten or has the wrong range.'));
            }
        }

        // summary.col_total = sum of summary.value for (block, partner).
        foreach ($idx['summary.col_total'] ?? [] as $block => $byPartner) {
            foreach ($byPartner as $partner => $cell) {
                $sum = 0.0; $components = [];
                foreach ($idx['summary.value'][$block] ?? [] as $date => $byP) {
                    if (!isset($byP[$partner])) continue;
                    $v = (float) $byP[$partner]['value'];
                    $sum += $v;
                    $components[] = ['label' => $date, 'value' => round($v, 2), 'ref' => $byP[$partner]['cell_ref']];
                }
                $this->compare($issues, $cell, ReportCheckIssue::KIND_COL_TOTAL,
                    $sum, (float)$cell['value'], self::TOL_EUR_MINOR, self::TOL_EUR_MAJOR,
                    "Col total {$block} {$partner}",
                    $this->explainInternalSum("Column total = sum of {$partner}'s daily cells in the {$block} block.", $components, 'Check the SUM formula range — a missing day or extra row would explain it.'));
            }
        }

        // summary.section_total = sum of summary.row_total for that block.
        foreach ($idx['summary.section_total'] ?? [] as $block => $cell) {
            $sum = 0.0; $components = [];
            foreach ($idx['summary.row_total'][$block] ?? [] as $date => $rt) {
                $v = (float) $rt['value']; $sum += $v;
                $components[] = ['label' => $date, 'value' => round($v, 2), 'ref' => $rt['cell_ref']];
            }
            $this->compare($issues, $cell, ReportCheckIssue::KIND_GRAND_TOTAL,
                $sum, (float)$cell['value'], self::TOL_EUR_MINOR, self::TOL_EUR_MAJOR,
                "Section total {$block}",
                $this->explainInternalSum("Block grand total = sum of daily Total column in the {$block} block.", $components, null));
        }

        // DAILY TOTALS row: per (date, block) = section block row total.
        foreach ($idx['summary.daily_total_row'] ?? [] as $date => $byBlock) {
            foreach ($byBlock as $block => $cell) {
                if ($block === SectionClassifier::INTERSCROLLER) {
                    $src = $idx['section_sheet.value'][SectionClassifier::INTERSCROLLER][$date] ?? null;
                    $expected = (float) ($src['value'] ?? 0);
                    $expl = $this->explainCrossRef("Interscroller has no Summary block — DAILY TOTALS pulls from the Interscroller sheet.",
                        $src ? [['label' => 'Interscroller!' . ($src['cell_ref'] ?? '?'), 'value' => round($expected, 2), 'ref' => $src['cell_ref']]] : [],
                        'Verify the Interscroller sheet has the right value for this date.');
                } else {
                    $src = $idx['summary.row_total'][$block][$date] ?? null;
                    $expected = (float) ($src['value'] ?? 0);
                    $expl = $this->explainCrossRef("DAILY TOTALS for {$block} on {$date} should mirror the {$block} block's row total.",
                        $src ? [['label' => "Summary!" . ($src['cell_ref'] ?? '?'), 'value' => round($expected, 2), 'ref' => $src['cell_ref']]] : [],
                        'These two cells show the same number — make sure the formula in DAILY TOTALS references the correct row.');
                }
                $this->compare($issues, $cell, ReportCheckIssue::KIND_DERIVED,
                    $expected, (float)$cell['value'], self::TOL_EUR_MINOR, self::TOL_EUR_MAJOR,
                    "Daily total {$date} {$block}", $expl);
            }
        }

        // DAILY TOTALS row total = sum of formats for that date.
        foreach ($idx['summary.daily_total_row_total'] ?? [] as $date => $cell) {
            $sum = 0.0; $components = [];
            foreach ($idx['summary.daily_total_row'][$date] ?? [] as $b => $bc) {
                $v = (float) $bc['value']; $sum += $v;
                $components[] = ['label' => ucfirst($b), 'value' => round($v, 2), 'ref' => $bc['cell_ref']];
            }
            $this->compare($issues, $cell, ReportCheckIssue::KIND_ROW_TOTAL,
                $sum, (float)$cell['value'], self::TOL_EUR_MINOR, self::TOL_EUR_MAJOR,
                "Daily total row total {$date}",
                $this->explainInternalSum("Row total in DAILY TOTALS = sum of the format cells (Display + Sticky + Inarticle + Interscroller) for {$date}.", $components, null));
        }

        // DAILY TOTALS col total = sum of dates for that format.
        foreach ($idx['summary.daily_total_col_total'] ?? [] as $block => $cell) {
            $sum = 0.0; $components = [];
            foreach ($idx['summary.daily_total_row'] ?? [] as $date => $byB) {
                if (!isset($byB[$block])) continue;
                $v = (float) $byB[$block]['value']; $sum += $v;
                $components[] = ['label' => $date, 'value' => round($v, 2), 'ref' => $byB[$block]['cell_ref']];
            }
            $this->compare($issues, $cell, ReportCheckIssue::KIND_COL_TOTAL,
                $sum, (float)$cell['value'], self::TOL_EUR_MINOR, self::TOL_EUR_MAJOR,
                "Daily total col total {$block}",
                $this->explainInternalSum("TOTAL row in DAILY TOTALS = sum of {$block} across all dates.", $components, null));
        }

        // summary.daily_grand = sum of daily_total_row_total.
        if (isset($idx['summary.daily_grand'])) {
            $sum = 0.0; $components = [];
            foreach ($idx['summary.daily_total_row_total'] ?? [] as $date => $rt) {
                $v = (float) $rt['value']; $sum += $v;
                $components[] = ['label' => $date, 'value' => round($v, 2), 'ref' => $rt['cell_ref']];
            }
            $this->compare($issues, $idx['summary.daily_grand'], ReportCheckIssue::KIND_GRAND_TOTAL,
                $sum, (float)$idx['summary.daily_grand']['value'], self::TOL_EUR_MINOR, self::TOL_EUR_MAJOR,
                'Daily totals grand',
                $this->explainInternalSum('TOTAL × Total in DAILY TOTALS = sum of the per-day Totals.', $components, null));
        }

        // SECTION TOTALS cells = corresponding col totals from the section block.
        foreach ($idx['summary.section_totals'] ?? [] as $block => $byPartner) {
            foreach ($byPartner as $partner => $cell) {
                $src = $idx['summary.col_total'][$block][$partner] ?? null;
                $expected = (float) ($src['value'] ?? 0);
                $this->compare($issues, $cell, ReportCheckIssue::KIND_DERIVED,
                    $expected, (float)$cell['value'], self::TOL_EUR_MINOR, self::TOL_EUR_MAJOR,
                    "Section totals {$block} {$partner}",
                    $this->explainCrossRef("SECTION TOTALS for {$partner} in {$block} should mirror the {$block} block's TOTAL row for {$partner}.",
                        $src ? [['label' => "Summary!" . ($src['cell_ref'] ?? '?'), 'value' => round($expected, 2), 'ref' => $src['cell_ref']]] : [],
                        'Re-link the SECTION TOTALS formula to the right TOTAL cell.'));
            }
        }

        // SECTION TOTALS row total = section_total of that block.
        foreach ($idx['summary.section_totals_row_total'] ?? [] as $block => $cell) {
            $src = $idx['summary.section_total'][$block] ?? null;
            $expected = (float) ($src['value'] ?? 0);
            $this->compare($issues, $cell, ReportCheckIssue::KIND_ROW_TOTAL,
                $expected, (float)$cell['value'], self::TOL_EUR_MINOR, self::TOL_EUR_MAJOR,
                "Section totals row total {$block}",
                $this->explainCrossRef("SECTION TOTALS row total for {$block} should mirror the {$block} block's grand total.",
                    $src ? [['label' => "Summary!" . ($src['cell_ref'] ?? '?'), 'value' => round($expected, 2), 'ref' => $src['cell_ref']]] : [],
                    null));
        }

        // GRAND TOTAL = sum of section_totals_row_total.
        if (isset($idx['summary.grand_total'])) {
            $sum = 0.0; $components = [];
            foreach ($idx['summary.section_totals_row_total'] ?? [] as $b => $rt) {
                $v = (float) $rt['value']; $sum += $v;
                $components[] = ['label' => ucfirst($b), 'value' => round($v, 2), 'ref' => $rt['cell_ref']];
            }
            $this->compare($issues, $idx['summary.grand_total'], ReportCheckIssue::KIND_GRAND_TOTAL,
                $sum, (float)$idx['summary.grand_total']['value'], self::TOL_EUR_MINOR, self::TOL_EUR_MAJOR,
                'Grand total',
                $this->explainInternalSum('GRAND TOTAL = sum of the SECTION TOTALS row totals (Display + Inarticle + Sticky).', $components, null));
        }
    }

    // ============================ PHASE C ============================

    private function checkCrossSheet(array $idx, array $truth, array &$issues): void
    {
        // Demand Partners value (date, partner) = sum across sections of summary.value.
        foreach ($idx['demand_partners.value'] ?? [] as $date => $byPartner) {
            foreach ($byPartner as $partner => $cell) {
                $sum = 0.0; $components = [];
                foreach ($idx['summary.value'] ?? [] as $block => $byDate) {
                    $vc = $byDate[$date][$partner] ?? null;
                    if (!$vc) continue;
                    $v = (float) $vc['value']; $sum += $v;
                    $components[] = ['label' => ucfirst($block), 'value' => round($v, 2), 'ref' => 'Summary!' . $vc['cell_ref']];
                }
                $isOgury = $partner === 'Ogury';
                [$minor, $major] = $isOgury
                    ? [self::TOL_OGURY_MINOR, self::TOL_OGURY_MAJOR]
                    : [self::TOL_EUR_MINOR, self::TOL_EUR_MAJOR];
                $this->compare($issues, $cell, ReportCheckIssue::KIND_DERIVED,
                    $sum, (float)$cell['value'], $minor, $major,
                    "Demand Partners {$date} {$partner}",
                    $this->explainInternalSum("Demand Partners shows {$partner} totalled across all sections (Display + Sticky + Inarticle).", $components, null));
            }
        }

        // Demand Partners total (date) = sum of demand_partners.value for that date.
        foreach ($idx['demand_partners.total'] ?? [] as $date => $cell) {
            $sum = 0.0; $components = [];
            foreach ($idx['demand_partners.value'][$date] ?? [] as $partner => $vc) {
                $v = (float) $vc['value']; $sum += $v;
                $components[] = ['label' => $partner, 'value' => round($v, 2), 'ref' => $vc['cell_ref']];
            }
            $this->compare($issues, $cell, ReportCheckIssue::KIND_ROW_TOTAL,
                $sum, (float)$cell['value'], self::TOL_EUR_MINOR, self::TOL_EUR_MAJOR,
                "Demand Partners total {$date}",
                $this->explainInternalSum("Total column = sum of all partner cells on this row.", $components, null));
        }

        // Per-section sheet value (block, date) — sum of in-house partners only.
        foreach ($idx['section_sheet.value'] ?? [] as $block => $byDate) {
            foreach ($byDate as $date => $cell) {
                $expected = 0.0; $components = [];
                if ($block === SectionClassifier::INTERSCROLLER) {
                    foreach ($truth[SectionClassifier::INTERSCROLLER][$date] ?? [] as $partner => $v) {
                        if (in_array($partner, self::PER_SECTION_SHEET_EXCLUDED_PARTNERS, true)) continue;
                        $expected += $v;
                        $components[] = ['label' => $partner . ' (raw)', 'value' => round($v, 2)];
                    }
                } else {
                    foreach ($idx['summary.value'][$block][$date] ?? [] as $partner => $vc) {
                        if (in_array($partner, self::PER_SECTION_SHEET_EXCLUDED_PARTNERS, true)) continue;
                        $v = (float) $vc['value'];
                        $expected += $v;
                        $components[] = ['label' => $partner, 'value' => round($v, 2), 'ref' => 'Summary!' . $vc['cell_ref']];
                    }
                }
                $excluded = implode(' + ', self::PER_SECTION_SHEET_EXCLUDED_PARTNERS);
                $this->compare($issues, $cell, ReportCheckIssue::KIND_DERIVED,
                    $expected, (float)$cell['value'], self::TOL_EUR_MINOR, self::TOL_EUR_MAJOR,
                    ucfirst($block) . " sheet {$date}",
                    $this->explainInternalSum(
                        "The {$cell['sheet']} sheet shows in-house programmatic only — {$excluded} are excluded by design.",
                        $components,
                        "If this number includes affiliate revenue, the formula in {$cell['sheet']}!{$cell['cell_ref']} is summing too many partners."));
            }
        }
    }

    // ============================ PHASE D ============================

    private function checkTrendAnalytics(array $idx, array $analyticsByDate, array &$issues): void
    {
        $metricToProperty = [
            'pageviews'        => ['prop' => 'pageviews',        'col' => 'Views'],
            'ad_requests'      => ['prop' => 'ad_requests',      'col' => 'Total Ad Requests'],
            'impressions_sold' => ['prop' => 'impressions_sold', 'col' => 'Impressions Sold'],
        ];

        foreach ($idx['trend.metric'] ?? [] as $date => $byMetric) {
            $a = $analyticsByDate[$date] ?? null;
            foreach ($metricToProperty as $metric => $info) {
                if (!isset($byMetric[$metric])) continue;
                $cell = $byMetric[$metric];
                if ($a === null) {
                    $issues[] = $this->issue('Trend', $cell['cell_ref'], ReportCheckIssue::KIND_MISSING_SOURCE,
                        ReportCheckIssue::SEVERITY_MAJOR, null, (float)$cell['value'], null,
                        "No analytics row for {$date}",
                        ['why' => "analytics.csv has no row for {$date} — Trend!{$cell['cell_ref']} cannot be verified.",
                         'sources' => ['analytics.csv'],
                         'hint' => 'Re-export analytics.csv covering the full report period.']);
                    continue;
                }
                $expected = (float) $a->{$info['prop']};
                $found = (float) $cell['value'];
                $delta = round($found - $expected, 4);
                if (abs($delta) > 0.5) {
                    $sev = abs($delta) > 100 ? ReportCheckIssue::SEVERITY_MAJOR : ReportCheckIssue::SEVERITY_MINOR;
                    $issues[] = $this->issue('Trend', $cell['cell_ref'], ReportCheckIssue::KIND_VALUE,
                        $sev, $expected, $found, $delta,
                        "Trend {$date} {$metric}: expected " . number_format($expected) . ", got " . number_format($found),
                        [
                            'why'        => "Direct value from analytics.csv → \"{$info['col']}\" column for {$date}.",
                            'sources'    => ['analytics.csv'],
                            'components' => [['label' => "analytics.csv → {$info['col']} ({$date})", 'value' => $expected]],
                            'hint'       => "Open analytics.csv, find the {$date} row, and confirm \"{$info['col']}\". The produced report disagrees with it by " . number_format(abs($delta)) . '.',
                        ]);
                }
            }
        }
    }

    // ============================ PHASE E ============================

    private function checkTrendDerived(array $idx, array $analyticsByDate, array &$issues): void
    {
        foreach ($idx['trend.metric'] ?? [] as $date => $byMetric) {
            $a = $analyticsByDate[$date] ?? null;
            $dailyCell = $idx['summary.daily_total_row_total'][$date] ?? null;
            $dailyTotal = (float) ($dailyCell['value'] ?? 0);

            // revenues = daily total
            if (isset($byMetric['revenues'])) {
                $cell = $byMetric['revenues'];
                $this->compare($issues, $cell, ReportCheckIssue::KIND_DERIVED,
                    $dailyTotal, (float)$cell['value'], self::TOL_EUR_MINOR, self::TOL_EUR_MAJOR,
                    "Trend {$date} Revenues",
                    $this->explainCrossRef("Trend > Revenues for {$date} should mirror DAILY TOTALS row total for the same date.",
                        $dailyCell ? [['label' => "Summary!" . ($dailyCell['cell_ref'] ?? '?'), 'value' => round($dailyTotal, 2), 'ref' => $dailyCell['cell_ref']]] : [],
                        'These two cells show the same number — re-link the Trend formula to DAILY TOTALS.'));
            }
            if ($a === null) continue;

            $pvCell  = $byMetric['pageviews']        ?? null;
            $reqCell = $byMetric['ad_requests']      ?? null;
            $isCell  = $byMetric['impressions_sold'] ?? null;
            $revCell = $byMetric['revenues']         ?? null;

            // RPM = revenues / pageviews × 1000
            if (isset($byMetric['rpm']) && $a->pageviews > 0) {
                $cell = $byMetric['rpm'];
                $expected = $dailyTotal / $a->pageviews * 1000.0;
                $this->compare($issues, $cell, ReportCheckIssue::KIND_DERIVED,
                    $expected, (float)$cell['value'], self::TOL_RATIO_MINOR, self::TOL_RATIO_MAJOR,
                    "Trend {$date} RPM",
                    $this->explainFormula('RPM = Revenue ÷ Pageviews × 1000', [
                        ['label' => 'Revenue',   'value' => round($dailyTotal, 2),  'ref' => $revCell['cell_ref'] ?? null],
                        ['label' => 'Pageviews', 'value' => $a->pageviews,          'ref' => $pvCell['cell_ref']  ?? null],
                    ], 'Recompute RPM with the values from this row.'));
            }

            // Ad request / pageview
            if (isset($byMetric['ad_req_per_pv']) && $a->pageviews > 0) {
                $cell = $byMetric['ad_req_per_pv'];
                $expected = $a->ad_requests / $a->pageviews;
                $this->compare($issues, $cell, ReportCheckIssue::KIND_DERIVED,
                    $expected, (float)$cell['value'], self::TOL_RATIO_MINOR, self::TOL_RATIO_MAJOR,
                    "Trend {$date} Ad request / pageview",
                    $this->explainFormula('Ad request / pageview = Total Ad Requests ÷ Pageviews', [
                        ['label' => 'Total Ad Requests', 'value' => $a->ad_requests, 'ref' => $reqCell['cell_ref'] ?? null],
                        ['label' => 'Pageviews',         'value' => $a->pageviews,   'ref' => $pvCell['cell_ref']  ?? null],
                    ], null));
            }

            // Sold trend = impressions_sold / ad_requests
            if (isset($byMetric['sold_trend']) && $a->ad_requests > 0) {
                $cell = $byMetric['sold_trend'];
                $expected = $a->impressions_sold / $a->ad_requests;
                $this->compare($issues, $cell, ReportCheckIssue::KIND_DERIVED,
                    $expected, (float)$cell['value'], self::TOL_PCT_MINOR, self::TOL_PCT_MAJOR,
                    "Trend {$date} Sold Trend",
                    $this->explainFormula('Sold Trend = Impressions Sold ÷ Total Ad Requests', [
                        ['label' => 'Impressions Sold',  'value' => $a->impressions_sold, 'ref' => $isCell['cell_ref']  ?? null],
                        ['label' => 'Total Ad Requests', 'value' => $a->ad_requests,      'ref' => $reqCell['cell_ref'] ?? null],
                    ], 'If Impressions Sold or Total Ad Requests is wrong, this percentage will also drift.'));
            }
        }
    }

    // ============================ HELPERS ============================

    private function compare(array &$issues, array $cell, string $kind, float $expected, float $found, float $minor, float $major, string $what, ?array $explanation = null): void
    {
        $delta = round($found - $expected, 4);
        if (abs($delta) <= $minor) return;
        $severity = abs($delta) > $major ? ReportCheckIssue::SEVERITY_MAJOR : ReportCheckIssue::SEVERITY_MINOR;
        $issues[] = $this->issue($cell['sheet'], $cell['cell_ref'], $kind, $severity, $expected, $found, $delta,
            sprintf('%s: expected %.2f, got %.2f (Δ %+.2f)', $what, $expected, $found, $delta),
            $explanation);
    }

    private function issue(string $sheet, ?string $cellRef, string $kind, string $severity, ?float $expected, ?float $found, ?float $delta, ?string $message, ?array $explanation = null): array
    {
        return [
            'sheet'       => $sheet,
            'cell_ref'    => $cellRef,
            'kind'        => $kind,
            'severity'    => $severity,
            'expected'    => $expected !== null ? round($expected, 4) : null,
            'found'       => $found !== null ? round($found, 4) : null,
            'delta'       => $delta !== null ? round($delta, 4) : null,
            'message'     => $message ?? '',
            'explanation' => $explanation,
        ];
    }

    private function verdict(array $issues): string
    {
        $hasMajor = false; $hasMinor = false;
        foreach ($issues as $i) {
            if ($i['severity'] === ReportCheckIssue::SEVERITY_MAJOR) $hasMajor = true;
            elseif ($i['severity'] === ReportCheckIssue::SEVERITY_MINOR) $hasMinor = true;
        }
        if ($hasMajor) return ReportCheck::STATUS_FAIL_MAJOR;
        if ($hasMinor) return ReportCheck::STATUS_FAIL_MINOR;
        return ReportCheck::STATUS_PASS;
    }

    // ============================ EXPLANATION BUILDERS ============================

    /**
     * Phase A — a section-block value cell that came from raw rows.
     * For Ogury we additionally surface the USD subtotal and FX rate so
     * the user can see where the EUR figure came from.
     */
    private function explainSummaryValue(string $block, string $date, string $partner, ?array $detail, ?float $fxRate): array
    {
        $source = self::PARTNER_SOURCE_FILE[$partner] ?? null;
        $components = [];

        if ($detail !== null && ($detail['currency'] ?? '') === 'USD' && $fxRate !== null) {
            $components[] = ['label' => "Sum of {$detail['row_count']} {$partner} row(s) for {$date}", 'value' => round($detail['local'], 2), 'currency' => 'USD'];
            $components[] = ['label' => 'FX rate (auto-fitted)', 'value' => round($fxRate, 4)];
            $components[] = ['label' => 'Converted to EUR', 'value' => round($detail['eur'], 2)];
            $hint = "Open {$source} and verify the {$partner} rows for {$date} matching this publisher. Then check whether the FX rate makes sense for the period.";
        } else {
            $components[] = ['label' => "Sum of " . ($detail['row_count'] ?? 0) . " {$partner} row(s) for {$date}", 'value' => round($detail['eur'] ?? 0, 2)];
            $hint = $source
                ? "Open {$source} and check the {$partner} {$block} rows for {$date}."
                : null;
        }

        return [
            'why'        => "Expected = sum of all {$partner} {$block} revenue rows for {$date}, taken from " . ($source ?? 'the source file') . '.',
            'sources'    => $source ? [$source] : [],
            'components' => $components,
            'hint'       => $hint,
        ];
    }

    /** Sum-of-cells inside the workbook (row totals, col totals, etc.). */
    private function explainInternalSum(string $why, array $components, ?string $hint): array
    {
        return [
            'why'        => $why,
            'components' => $components,
            'hint'       => $hint,
        ];
    }

    /** Cross-reference: this cell should mirror another cell. */
    private function explainCrossRef(string $why, array $components, ?string $hint): array
    {
        return [
            'why'        => $why,
            'components' => $components,
            'hint'       => $hint,
        ];
    }

    /** Formula-driven derivation (RPM, ratios). */
    private function explainFormula(string $formula, array $components, ?string $hint): array
    {
        return [
            'why'        => $formula,
            'formula'    => $formula,
            'components' => $components,
            'hint'       => $hint,
        ];
    }

    private function explainRateOutlier(array $fxFit): array
    {
        return [
            'why'        => 'FX rate is auto-fitted by dividing total Ogury EUR (from the produced report) by total Ogury USD (from the raw Ogury.xlsx).',
            'components' => [
                ['label' => 'Total Ogury USD (raw)',     'value' => round($fxFit['total_usd'], 2), 'currency' => 'USD'],
                ['label' => 'Total Ogury EUR (outcome)', 'value' => round($fxFit['total_eur'], 2)],
                ['label' => 'Implied rate',              'value' => $fxFit['rate'] !== null ? round($fxFit['rate'], 4) : null],
            ],
            'sources'    => ['Ogury.xlsx'],
            'hint'       => 'Either the Ogury raw rows are wrong, or the EUR conversion in the produced report used a very different rate than expected. Compare a couple of cells manually.',
        ];
    }

    // ============================================================================

    private function totals(array $truth): array
    {
        $bySection = []; $byPartner = []; $byDate = []; $grand = 0.0;
        foreach ($truth as $section => $byD) {
            foreach ($byD as $date => $byP) {
                foreach ($byP as $partner => $eur) {
                    $bySection[$section] = ($bySection[$section] ?? 0) + $eur;
                    $byPartner[$partner] = ($byPartner[$partner] ?? 0) + $eur;
                    $byDate[$date] = ($byDate[$date] ?? 0) + $eur;
                    $grand += $eur;
                }
            }
        }
        return [
            'grand'      => round($grand, 2),
            'by_section' => array_map(fn($v) => round($v, 2), $bySection),
            'by_partner' => array_map(fn($v) => round($v, 2), $byPartner),
            'by_date'    => array_map(fn($v) => round($v, 2), $byDate),
        ];
    }
}
