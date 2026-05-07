<?php

namespace App\Services\ReportCheck;

use App\Models\ReportCheck;
use App\Models\ReportCheckFile;
use App\Models\ReportCheckIssue;
use App\Models\ReportCheckRevenue;
use App\Services\ReportCheck\Parsers\AdformParser;
use App\Services\ReportCheck\Parsers\AdheseParser;
use App\Services\ReportCheck\Parsers\AnalyticsParser;
use App\Services\ReportCheck\Parsers\Contract as RevenueParserContract;
use App\Services\ReportCheck\Parsers\GamParser;
use App\Services\ReportCheck\Parsers\OguryParser;
use App\Services\ReportCheck\Parsers\OutbrainParser;
use App\Services\ReportCheck\Parsers\SeedTagParser;
use App\Services\ReportCheck\Parsers\ShowHeroesParser;
use App\Services\ReportCheck\Parsers\TeadsParser;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Orchestrates a single validation run end-to-end:
 *   parse raw → read outcome → fit FX → validate → persist.
 *
 * Caller hands in a map of source_key => absolute file path. Files are
 * not stored anywhere by the runner (the controller deletes the temp
 * uploads after we return).
 */
class ReportCheckRunner
{
    /**
     * @param array<string,array{path:string,filename:string,sha256:string}> $files
     *        Keyed by source key (adform, gam, ..., outcome).
     */
    public function run(array $files, string $publisherSlug, ?int $uploadedBy): ReportCheck
    {
        $publisher = PublisherConfig::load($publisherSlug);

        $check = new ReportCheck([
            'publisher'        => $publisherSlug,
            'period_start'    => now()->toDateString(),  // overwritten once parsed
            'period_end'      => now()->toDateString(),
            'outcome_filename' => $files['outcome']['filename'] ?? '',
            'status'           => ReportCheck::STATUS_PENDING,
            'uploaded_by'      => $uploadedBy,
        ]);
        $check->save();

        // File audit rows up front so a parse crash still leaves a trace.
        foreach ($files as $key => $f) {
            ReportCheckFile::create([
                'check_id'         => $check->id,
                'source_key'       => $key,
                'filename'         => $f['filename'],
                'sha256'           => $f['sha256'],
                'parsed_row_count' => 0,
            ]);
        }

        try {
            $revenueRows = [];
            $rowCounts = [];

            foreach ($this->revenueParsers() as $sourceKey => $parser) {
                if (!isset($files[$sourceKey])) continue;
                $rows = $parser->parse($files[$sourceKey]['path'], $publisher);
                $revenueRows = array_merge($revenueRows, $rows);
                $rowCounts[$sourceKey] = count($rows);
            }

            $analyticsRows = [];
            if (isset($files['analytics'])) {
                $analyticsRows = (new AnalyticsParser())->parse($files['analytics']['path'], $publisher);
                $rowCounts['analytics'] = count($analyticsRows);
            }

            $outcomeSnapshot = ['cells' => [], 'errors' => ['Outcome file not provided'], 'period_label' => null];
            if (isset($files['outcome'])) {
                $outcomeSnapshot = (new OutcomeReader())->read($files['outcome']['path']);
                $rowCounts['outcome'] = count($outcomeSnapshot['cells']);
            }

            $fx = (new FxRateFitter())->fit($revenueRows, $outcomeSnapshot['cells']);

            $result = (new ReportValidator())->validate($revenueRows, $analyticsRows, $outcomeSnapshot, $fx);

            DB::transaction(function () use ($check, $result, $outcomeSnapshot, $analyticsRows, $fx, $rowCounts) {
                // Persist truth.
                foreach (array_chunk($result['revenues'], 200) as $chunk) {
                    $now = now();
                    $rows = array_map(fn($r) => $r + ['check_id' => $check->id, 'created_at' => $now, 'updated_at' => $now], $chunk);
                    ReportCheckRevenue::insert($rows);
                }

                // Persist issues. Bulk insert() bypasses Eloquent casts, so
                // encode the explanation array to JSON manually here.
                foreach (array_chunk($result['issues'], 200) as $chunk) {
                    $now = now();
                    $rows = array_map(function ($i) use ($check, $now) {
                        $i['explanation'] = isset($i['explanation']) && $i['explanation'] !== null ? json_encode($i['explanation']) : null;
                        return $i + ['check_id' => $check->id, 'created_at' => $now, 'updated_at' => $now];
                    }, $chunk);
                    ReportCheckIssue::insert($rows);
                }

                // Update file row counts.
                foreach ($rowCounts as $key => $n) {
                    ReportCheckFile::where('check_id', $check->id)->where('source_key', $key)->update(['parsed_row_count' => $n]);
                }

                // Derive period from the truth.
                $dates = collect($result['revenues'])->pluck('date')->all();
                if (!empty($dates)) {
                    sort($dates);
                    $check->period_start = $dates[0];
                    $check->period_end = end($dates);
                }

                $check->status            = $result['status'];
                $check->fx_rate_used      = $fx['rate'];
                $check->outcome_snapshot  = $outcomeSnapshot;
                $check->analytics_snapshot = array_map(fn($a) => [
                    'date'             => $a->date,
                    'pageviews'        => $a->pageviews,
                    'ad_requests'      => $a->ad_requests,
                    'impressions_sold' => $a->impressions_sold,
                ], $analyticsRows);
                $check->totals_snapshot   = $result['totals'];
                $check->save();
            });

        } catch (Throwable $e) {
            $check->status = ReportCheck::STATUS_ERROR;
            $check->error_message = $e->getMessage();
            $check->save();
            ReportCheckIssue::create([
                'check_id' => $check->id,
                'sheet'    => '-',
                'cell_ref' => null,
                'kind'     => ReportCheckIssue::KIND_PARSE_ERROR,
                'severity' => ReportCheckIssue::SEVERITY_MAJOR,
                'expected' => null,
                'found'    => null,
                'delta'    => null,
                'message'  => 'Run failed: ' . $e->getMessage(),
            ]);
        }

        return $check->fresh();
    }

    /** @return array<string,RevenueParserContract> */
    private function revenueParsers(): array
    {
        return [
            'adform'     => new AdformParser(),
            'gam'        => new GamParser(),
            'ogury'      => new OguryParser(),
            'seedtag'    => new SeedTagParser(),
            'showheroes' => new ShowHeroesParser(),
            'teads'      => new TeadsParser(),
            'adhese'     => new AdheseParser(),
            'outbrain'   => new OutbrainParser(),
        ];
    }
}
