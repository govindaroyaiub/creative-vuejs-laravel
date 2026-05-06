<?php

namespace App\Http\Controllers;

use App\Models\ReportRevenue;
use App\Models\ReportSource;
use App\Services\ReportFileParser;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function __construct(private readonly ReportFileParser $parser) {}

    public function index(Request $request)
    {
        [$start, $end, $period] = $this->resolvePeriod($request);
        $sectionedSources = $this->loadSectionedSources();
        $payload = $this->buildPayload($start, $end, $sectionedSources);
        $latestWeek = $this->latestWeekFor($start, $end);
        // Two fixed comparison cards, independent of the period being viewed.
        // Anchored to the most recent data we have so they're always meaningful.
        [$momCard, $yoyCard] = $this->momAndYoyCards();

        return Inertia::render('GenerateReports/Index', [
            'period' => $period,
            'latestWeek' => $latestWeek,
            'momCard' => $momCard,
            'yoyCard' => $yoyCard,
            'sections' => $payload['sections'],
            'rows' => $payload['rows'],
            'totals' => $payload['totals'],
            'availablePeriods' => $this->availablePeriods(),
            'sources' => ReportSource::query()
                ->where('active', true)
                ->orderBy('section')->orderBy('column_order')
                ->get(['id', 'key', 'display_name', 'section', 'filename_pattern']),
        ]);
    }

    public function upload(Request $request)
    {
        $request->validate([
            'files' => 'required|array|min:1',
            'files.*' => 'file|max:20480|mimes:xlsx,xls,csv,txt',
        ]);

        $accepted = [];
        $skipped = [];
        $rowsTouched = 0;

        foreach ($request->file('files') as $file) {
            $original = $file->getClientOriginalName();
            $source = $this->parser->detectSource($original);
            if (! $source) {
                $skipped[] = ['file' => $original, 'reason' => 'no matching source (filename did not match any registered pattern)'];
                continue;
            }

            try {
                $rows = $this->parser->parse($file->getRealPath(), $source);
            } catch (\Throwable $e) {
                Log::error('Report file parse failed', [
                    'file' => $original,
                    'source' => $source->key,
                    'error' => $e->getMessage(),
                ]);
                $skipped[] = ['file' => $original, 'reason' => 'parse failed: ' . $e->getMessage()];
                continue;
            }

            if (empty($rows)) {
                $skipped[] = ['file' => $original, 'reason' => 'no rows extracted'];
                continue;
            }

            // Pull the week number out of the filename ("... - Week 05.xlsx" -> 5).
            $week = $this->extractWeekFromFilename($original);

            $now = now();
            $userId = Auth::id();
            $writes = 0;
            $protected = 0;
            DB::transaction(function () use ($rows, $source, $userId, $now, $week, &$writes, &$protected) {
                foreach ($rows as $date => $revenue) {
                    $existing = ReportRevenue::where('source_id', $source->id)
                        ->where('date', $date)
                        ->first();

                    // Skip when the stored row came from a higher week (newer cumulative cycle)
                    // — protects corrected numbers from being rolled back by an older re-upload.
                    if ($existing && $week !== null && $existing->source_week !== null && $existing->source_week > $week) {
                        $protected++;
                        continue;
                    }

                    ReportRevenue::updateOrCreate(
                        ['source_id' => $source->id, 'date' => $date],
                        [
                            'revenue' => $revenue,
                            'source_week' => $week,
                            'uploaded_by' => $userId,
                            'uploaded_at' => $now,
                        ],
                    );
                    $writes++;
                }
            });
            $rowsTouched += $writes;

            $accepted[] = [
                'file' => $original,
                'source' => $source->display_name . ' / ' . $source->section,
                'rows' => count($rows),
                'written' => $writes,
                'protected' => $protected,
                'week' => $week,
                'first_date' => array_key_first($rows),
                'last_date' => array_key_last($rows),
            ];
        }

        return back()->with('upload_result', [
            'accepted' => $accepted,
            'skipped' => $skipped,
            'rows_touched' => $rowsTouched,
        ]);
    }

    public function export(Request $request)
    {
        $request->validate([
            'format' => 'required|in:csv,xlsx,json,pdf',
            'view' => 'nullable|in:month,year,custom',
            'month' => 'nullable|date_format:Y-m',
            'year' => 'nullable|integer|min:2000|max:2100',
            'start' => 'nullable|date_format:Y-m-d',
            'end' => 'nullable|date_format:Y-m-d|after_or_equal:start',
        ]);

        [$start, $end, $period] = $this->resolvePeriod($request);
        $sectionedSources = $this->loadSectionedSources();
        $payload = $this->buildPayload($start, $end, $sectionedSources);
        // Exports use the filename week ("source_week"), not the period-derived
        // calendar week. The page pill uses the calendar week — the export
        // artifact carries the data's official upload label.
        $exportWeek = $this->latestSourceWeekFor($start, $end);

        $weekSlug = $exportWeek !== null ? '-week-' . str_pad((string) $exportWeek, 2, '0', STR_PAD_LEFT) : '';
        $filename = 'comparative-revenue-' . $period['slug'] . $weekSlug;

        return match ($request->input('format')) {
            'csv' => $this->exportCsv($filename . '.csv', $period, $payload, $exportWeek),
            'xlsx' => $this->exportXlsx($filename . '.xlsx', $period, $payload, $exportWeek),
            'pdf' => $this->exportPdf($filename . '.pdf', $period, $payload, $exportWeek),
            'json' => response()->json([
                'period' => $period,
                'latestWeek' => $exportWeek,
                'sections' => $payload['sections'],
                'rows' => $payload['rows'],
                'totals' => $payload['totals'],
            ])->header(
                'Content-Disposition',
                'attachment; filename="' . $filename . '.json"'
            ),
        };
    }

    private function exportPdf(string $filename, array $period, array $payload, ?int $latestWeek): Response
    {
        $pdf = Pdf::loadView('pdf.comparative-analysis', [
            'period' => $period,
            'latestWeek' => $latestWeek,
            'sections' => $payload['sections'],
            'rows' => $payload['rows'],
            'totals' => $payload['totals'],
        ])->setPaper('a4', 'portrait');

        return $pdf->download($filename);
    }

    /**
     * Calendar week of the latest data point in the chosen date range, using
     * the user's "Week N = days 1 … 7N from Jan 1" convention. Used for the
     * page pill so the label changes when the user navigates between months
     * (Jan → Week 5, Apr → Week 18, etc.).
     */
    private function latestWeekFor(CarbonImmutable $start, CarbonImmutable $end): ?int
    {
        $maxDate = ReportRevenue::query()
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->max('date');
        if (! $maxDate) return null;

        $dayOfYear = (int) Carbon::parse($maxDate)->dayOfYear;
        return (int) ceil($dayOfYear / 7);
    }

    /**
     * Highest filename-week stored for any row in the chosen date range.
     * Used by the exports (PDF/CSV/XLSX) so the file's title carries the
     * upload's official week number rather than a period-derived one.
     */
    private function latestSourceWeekFor(CarbonImmutable $start, CarbonImmutable $end): ?int
    {
        $w = ReportRevenue::query()
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->max('source_week');
        return $w === null ? null : (int) $w;
    }

    /**
     * Two fixed comparison cards (always shown) anchored to the most recent
     * data we have:
     *   - M-o-M: full month containing the latest data point vs the prior month.
     *   - Y-o-Y: full year containing the latest data point vs the prior year.
     * Returns [null, null] only if the database has no rows at all.
     *
     * @return array{0: array|null, 1: array|null}
     */
    private function momAndYoyCards(): array
    {
        $latest = ReportRevenue::max('date');
        if (! $latest) return [null, null];
        $latestDate = Carbon::parse($latest);

        $sumBetween = fn (Carbon $s, Carbon $e) => (float) ReportRevenue::query()
            ->whereBetween('date', [$s->toDateString(), $e->toDateString()])
            ->sum('revenue');

        // M-o-M
        $curMStart = $latestDate->copy()->startOfMonth();
        $curMEnd   = $curMStart->copy()->endOfMonth();
        $prvMStart = $curMStart->copy()->subMonthNoOverflow()->startOfMonth();
        $prvMEnd   = $prvMStart->copy()->endOfMonth();
        $mom = [
            'kind' => 'mom',
            'current_label'  => $curMStart->format('F Y'),
            'current_short'  => $curMStart->format('M Y'),
            'previous_label' => $prvMStart->format('F Y'),
            'previous_short' => $prvMStart->format('M Y'),
            'current_total'  => $sumBetween($curMStart, $curMEnd),
            'previous_total' => $sumBetween($prvMStart, $prvMEnd),
        ];

        // Y-o-Y
        $curYStart = $latestDate->copy()->startOfYear();
        $curYEnd   = $curYStart->copy()->endOfYear();
        $prvYStart = $curYStart->copy()->subYearNoOverflow()->startOfYear();
        $prvYEnd   = $prvYStart->copy()->endOfYear();
        $yoy = [
            'kind' => 'yoy',
            'current_label'  => (string) $curYStart->year,
            'current_short'  => (string) $curYStart->year,
            'previous_label' => (string) $prvYStart->year,
            'previous_short' => (string) $prvYStart->year,
            'current_total'  => $sumBetween($curYStart, $curYEnd),
            'previous_total' => $sumBetween($prvYStart, $prvYEnd),
        ];

        return [$mom, $yoy];
    }

    /**
     * @deprecated kept temporarily — unused after MoM/YoY simplification.
     */
    private function previousPeriodComparison(CarbonImmutable $start, CarbonImmutable $end, array $period): ?array
    {
        if ($period['type'] === 'month') {
            $prevStart = $start->subMonth()->startOfMonth();
            $prevEnd = $prevStart->endOfMonth();
            $label = $prevStart->format('F Y');
            $shortLabel = $prevStart->format('M Y');
        } elseif ($period['type'] === 'year') {
            $prevStart = $start->subYear()->startOfYear();
            $prevEnd = $prevStart->endOfYear();
            $label = (string) $prevStart->year;
            $shortLabel = $label;
        } else { // custom — same-length range immediately before $start
            $days = $start->diffInDays($end) + 1;
            $prevEnd = $start->subDay()->endOfDay();
            $prevStart = $prevEnd->subDays($days - 1)->startOfDay();
            $label = $prevStart->format('M j, Y') . ' – ' . $prevEnd->format('M j, Y');
            $shortLabel = $prevStart->format('M j') . '–' . $prevEnd->format('M j');
        }

        $total = (float) ReportRevenue::query()
            ->whereBetween('date', [$prevStart->toDateString(), $prevEnd->toDateString()])
            ->sum('revenue');

        return [
            'label' => $label,
            'short_label' => $shortLabel,
            'start' => $prevStart->toDateString(),
            'end' => $prevEnd->toDateString(),
            'total' => $total,
        ];
    }

    /**
     * Year-over-year comparison — same period shifted back by exactly 1 year
     * (Apr 2026 → Apr 2025, year 2026 → 2025, custom range → same dates last year).
     * Returns null when the prior-year window has no data, or when it would
     * collapse onto the M-o-M comparison (e.g. Year view where both = "last year").
     */
    private function priorYearComparison(CarbonImmutable $start, CarbonImmutable $end, array $period): ?array
    {
        $prevStart = $start->subYearNoOverflow()->startOfDay();
        $prevEnd = $end->subYearNoOverflow()->endOfDay();

        if ($period['type'] === 'month') {
            $label = $prevStart->format('F Y');
            $shortLabel = $prevStart->format('M Y');
        } elseif ($period['type'] === 'year') {
            $label = (string) $prevStart->year;
            $shortLabel = $label;
        } else {
            $label = $prevStart->format('M j, Y') . ' – ' . $prevEnd->format('M j, Y');
            $shortLabel = $prevStart->format('M j, Y') . '–' . $prevEnd->format('M j, Y');
        }

        $total = (float) ReportRevenue::query()
            ->whereBetween('date', [$prevStart->toDateString(), $prevEnd->toDateString()])
            ->sum('revenue');

        return [
            'label' => $label,
            'short_label' => $shortLabel,
            'start' => $prevStart->toDateString(),
            'end' => $prevEnd->toDateString(),
            'total' => $total,
        ];
    }

    /**
     * Pull the week number out of a filename like "GAM - Outstream - TG - Week 05.xlsx".
     * Returns null if the filename has no recognizable week marker.
     */
    private function extractWeekFromFilename(string $filename): ?int
    {
        if (preg_match('/week[\s_-]*0*(\d{1,2})/i', $filename, $m)) {
            $n = (int) $m[1];
            return ($n >= 1 && $n <= 53) ? $n : null;
        }
        return null;
    }

    private function resolvePeriod(Request $request): array
    {
        $view = $request->input('view', 'month');

        if ($view === 'custom') {
            $startStr = $request->input('start');
            $endStr = $request->input('end');
            // Fall back to the most recent uploaded month if either bound is missing.
            if (! $startStr || ! $endStr) {
                $latest = ReportRevenue::max('date');
                $month = $latest ? Carbon::parse($latest)->format('Y-m') : now()->format('Y-m');
                $startStr = $startStr ?: $month . '-01';
                $endStr = $endStr ?: Carbon::parse($month . '-01')->endOfMonth()->toDateString();
            }
            $start = CarbonImmutable::parse($startStr)->startOfDay();
            $end = CarbonImmutable::parse($endStr)->endOfDay();
            if ($end->lt($start)) [$start, $end] = [$end, $start];

            $sameYear = $start->year === $end->year;
            $label = $sameYear
                ? $start->format('M j') . ' – ' . $end->format('M j, Y')
                : $start->format('M j, Y') . ' – ' . $end->format('M j, Y');

            return [$start, $end, [
                'type' => 'custom',
                'start' => $start->toDateString(),
                'end' => $end->toDateString(),
                'label' => $label,
                'slug' => $start->format('Ymd') . '-' . $end->format('Ymd'),
            ]];
        }

        if ($view === 'year') {
            $year = (int) $request->input('year', now()->year);
            $start = CarbonImmutable::create($year, 1, 1)->startOfDay();
            $end = $start->endOfYear();
            return [$start, $end, [
                'type' => 'year',
                'year' => $year,
                'label' => (string) $year,
                'slug' => (string) $year,
            ]];
        }

        $month = $request->input('month');
        if (! $month) {
            $latest = ReportRevenue::max('date');
            $month = $latest
                ? Carbon::parse($latest)->format('Y-m')
                : now()->format('Y-m');
        }
        $start = CarbonImmutable::createFromFormat('Y-m-d', $month . '-01')->startOfDay();
        $end = $start->endOfMonth();

        return [$start, $end, [
            'type' => 'month',
            'month' => $month,
            'label' => $start->format('F Y'),
            'slug' => $month,
        ]];
    }

    /**
     * @return array<string, \Illuminate\Support\Collection<int, ReportSource>>
     */
    private function loadSectionedSources(): array
    {
        $sources = ReportSource::query()
            ->where('active', true)
            ->orderBy('section')
            ->orderBy('column_order')
            ->get();

        $sectionOrder = ['Outstream', 'Sticky'];
        $grouped = $sources->groupBy('section');

        // Preserve preferred section order with any extra sections appended.
        $ordered = [];
        foreach ($sectionOrder as $name) {
            if ($grouped->has($name)) {
                $ordered[$name] = $grouped[$name];
            }
        }
        foreach ($grouped as $name => $items) {
            if (! isset($ordered[$name])) {
                $ordered[$name] = $items;
            }
        }
        return $ordered;
    }

    private function buildPayload(CarbonImmutable $start, CarbonImmutable $end, array $sectionedSources): array
    {
        $sourceById = [];
        $sectionsForVue = [];
        foreach ($sectionedSources as $section => $sources) {
            $sectionEntry = ['name' => $section, 'sources' => []];
            foreach ($sources as $source) {
                $sourceById[$source->id] = $source;
                $sectionEntry['sources'][] = [
                    'key' => $source->key,
                    'display_name' => $source->display_name,
                ];
            }
            $sectionsForVue[] = $sectionEntry;
        }

        $rows = ReportRevenue::query()
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('date')
            ->get(['source_id', 'date', 'revenue']);

        // Group revenues by date, then by source key.
        $byDate = [];
        $totals = [];
        foreach ($rows as $row) {
            $source = $sourceById[$row->source_id] ?? null;
            if (! $source) continue;
            $dateKey = Carbon::parse($row->date)->format('Y-m-d');
            $value = (float) $row->revenue;
            $byDate[$dateKey][$source->key] = $value;
            $totals[$source->key] = ($totals[$source->key] ?? 0.0) + $value;
        }

        ksort($byDate);
        $rowsForVue = [];
        foreach ($byDate as $date => $cells) {
            $rowsForVue[] = ['date' => $date, 'cells' => $cells];
        }

        return [
            'sections' => $sectionsForVue,
            'rows' => $rowsForVue,
            'totals' => $totals,
        ];
    }

    private function availablePeriods(): array
    {
        // DB-agnostic: pull distinct dates (cheap; bounded by year * sources),
        // then bucket to Y-m in PHP. Works on SQLite, MySQL, Postgres alike.
        $dates = ReportRevenue::query()
            ->distinct()
            ->orderBy('date')
            ->pluck('date')
            ->all();

        $months = [];
        foreach ($dates as $date) {
            $month = Carbon::parse($date)->format('Y-m');
            if (! in_array($month, $months, true)) {
                $months[] = $month;
            }
        }

        return [
            'months' => $months,
            'years' => array_values(array_unique(array_map(fn($m) => substr($m, 0, 4), $months))),
        ];
    }

    private function exportCsv(string $filename, array $period, array $payload, ?int $latestWeek = null): StreamedResponse
    {
        return response()->streamDownload(function () use ($period, $payload, $latestWeek) {
            $out = fopen('php://output', 'w');
            // BOM so Excel opens UTF-8 cleanly.
            fwrite($out, "\xEF\xBB\xBF");
            // RFC 4180 CSV: comma separator, quote enclosure, no escape char.
            $write = static fn (array $row) => fputcsv($out, $row, ',', '"', '');

            $title = $latestWeek !== null ? 'Week ' . str_pad((string) $latestWeek, 2, '0', STR_PAD_LEFT) : $period['label'];
            $write(['Comparative Analysis of Revenue', $title, $period['label']]);
            $write([]);

            foreach ($payload['sections'] as $section) {
                $sectionLabel = $section['name'] === 'Outstream' ? 'Outstream Ad Position' : ($section['name'] === 'Sticky' ? 'Sticky Bottom Ad Position' : $section['name']);
                $write([$sectionLabel]);

                $header = ['Date'];
                foreach ($section['sources'] as $src) $header[] = $src['display_name'];
                $write($header);

                foreach ($payload['rows'] as $row) {
                    $line = [Carbon::parse($row['date'])->format('n/j/Y')];
                    foreach ($section['sources'] as $src) {
                        $line[] = isset($row['cells'][$src['key']])
                            ? number_format($row['cells'][$src['key']], 2, '.', '')
                            : '';
                    }
                    $write($line);
                }

                $totalLine = ['Total'];
                foreach ($section['sources'] as $src) {
                    $totalLine[] = isset($payload['totals'][$src['key']])
                        ? number_format($payload['totals'][$src['key']], 2, '.', '')
                        : '0.00';
                }
                $write($totalLine);
                $write([]);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function exportXlsx(string $filename, array $period, array $payload, ?int $latestWeek = null): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $title = $latestWeek !== null ? 'Week ' . str_pad((string) $latestWeek, 2, '0', STR_PAD_LEFT) : $period['label'];
        $sheet->setTitle(substr('Revenue ' . $title, 0, 31));

        $rowIdx = 1;
        $sheet->setCellValue('A' . $rowIdx, 'Comparative Analysis of Revenue');
        $sheet->setCellValue('B' . $rowIdx, $title);
        $sheet->setCellValue('C' . $rowIdx, $period['label']);
        $sheet->getStyle('A' . $rowIdx . ':C' . $rowIdx)->getFont()->setBold(true);
        $rowIdx += 2;

        $eurFormat = '"€" #,##0.00';

        foreach ($payload['sections'] as $section) {
            $sectionLabel = $section['name'] === 'Outstream'
                ? 'Outstream Ad Position'
                : ($section['name'] === 'Sticky' ? 'Sticky Bottom Ad Position' : $section['name']);
            $sheet->setCellValue('A' . $rowIdx, $sectionLabel);
            $sheet->getStyle('A' . $rowIdx)->getFont()->setBold(true);
            $rowIdx++;

            // Header row
            $col = 'A';
            $sheet->setCellValue($col . $rowIdx, 'Date');
            foreach ($section['sources'] as $src) {
                $col++;
                $sheet->setCellValue($col . $rowIdx, $src['display_name']);
            }
            $sheet->getStyle('A' . $rowIdx . ':' . $col . $rowIdx)->getFont()->setBold(true);
            $rowIdx++;

            // Data rows
            foreach ($payload['rows'] as $row) {
                $col = 'A';
                $sheet->setCellValue($col . $rowIdx, Carbon::parse($row['date'])->format('n/j/Y'));
                foreach ($section['sources'] as $src) {
                    $col++;
                    $val = $row['cells'][$src['key']] ?? null;
                    if ($val !== null) {
                        $sheet->setCellValue($col . $rowIdx, $val);
                        $sheet->getStyle($col . $rowIdx)->getNumberFormat()->setFormatCode($eurFormat);
                    }
                }
                $rowIdx++;
            }

            // Total row
            $col = 'A';
            $sheet->setCellValue($col . $rowIdx, 'Total');
            foreach ($section['sources'] as $src) {
                $col++;
                $sheet->setCellValue($col . $rowIdx, $payload['totals'][$src['key']] ?? 0);
                $sheet->getStyle($col . $rowIdx)->getNumberFormat()->setFormatCode($eurFormat);
            }
            $sheet->getStyle('A' . $rowIdx . ':' . $col . $rowIdx)->getFont()->setBold(true);
            $rowIdx += 2;
        }

        // Auto-size used columns.
        foreach (range('A', $sheet->getHighestColumn()) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
