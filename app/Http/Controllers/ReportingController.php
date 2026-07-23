<?php

namespace App\Http\Controllers;

use App\Models\ReportDay;
use App\Models\ReportSetting;
use App\Services\Reporting\ReportProcessor;
use App\Services\Reporting\ReportStore;
use App\Services\Reporting\Reporting;
use App\Services\Reporting\TableExporter;
use App\Services\Reporting\Verifier;
use App\Services\Reporting\ZipBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Inertia\Inertia;

class ReportingController extends Controller
{
    /** Where each partner report is downloaded from (defaults; user-editable). */
    private const DEFAULT_LINKS = [
        ['label' => 'SeedTag', 'url' => 'https://publisher-panel.seedtag.com/'],
        ['label' => 'Teads', 'url' => 'https://login.teads.tv/login'],
        ['label' => 'Showheroes', 'url' => 'https://platform.showheroes.com/app/user/10360/'],
        ['label' => 'GAM', 'url' => 'https://admanager.google.com/21759686865#reports/interactive/list?redirected=true'],
        ['label' => 'Adform', 'url' => 'https://www.adform.com/DirectIntegrationsUI/#/publishers-home/YieldManager'],
        ['label' => 'Ogury', 'url' => 'https://publishers.ogury.co/identity/login?redirect=%2Fexclusive-demand%2Freports%2Fad-unit'],
        ['label' => 'Outbrain', 'url' => 'https://my.outbrain.com/login'],
        ['label' => 'Analytics', 'url' => 'https://analytics.google.com/analytics/web/#/a89733213p312613556/reports/intelligenthome'],
        ['label' => 'Looker Studio', 'url' => 'https://datastudio.google.com/reporting/ef35717e-5742-44c1-80d5-b134e54b9002/page/8WngB'],
        ['label' => 'Planetnine', 'url' => 'https://admin.planetnine.com/admin/reporting'],
    ];

    /** Where canonical uploaded files + regenerated CSVs live for the ZIP download. */
    private function uploadsDir(): string
    {
        $dir = storage_path('app/reporting/uploads');
        if (! is_dir($dir)) mkdir($dir, 0775, true);

        return $dir;
    }

    /** Base props shared by the page render. */
    private function baseProps(): array
    {
        // Load all page settings in one query, then read from the map (each setting
        // was previously its own SELECT).
        $settings = ReportSetting::whereIn('key', [
            'report_links', 'reminder_day', 'rpm_amber', 'rpm_red', 'ogury_old_format', 'file_patterns',
        ])->get()->keyBy('key');
        $setting = fn (string $key, $default = null) => $settings->has($key) ? $settings->get($key)->value : $default;

        return [
            'store' => ReportStore::load(),
            'sites' => collect(Reporting::SITES)->map(fn ($cfg, $id) => ['id' => $id, 'name' => $cfg['name']])->values(),
            'uploadFiles' => ZipBuilder::availableFiles($this->uploadsDir()),
            'reportLinks' => $setting('report_links', self::DEFAULT_LINKS),
            'reminderDay' => (int) $setting('reminder_day', 3),
            'rpmAmber' => (float) $setting('rpm_amber', 7.5),
            'rpmRed' => (float) $setting('rpm_red', 8),
            'oguryOldFormat' => (bool) $setting('ogury_old_format', false),
            'filePatterns' => array_merge(Reporting::DEFAULT_FILE_PATTERNS, (array) $setting('file_patterns', [])),
            'sync' => $this->syncInfo(),
        ];
    }

    /** Path to the committed data snapshot that travels with git. */
    private function exportFile(): string
    {
        return base_path('database/reporting-export.json');
    }

    private function fileExportedAt(): ?string
    {
        $p = $this->exportFile();
        if (! is_file($p)) return null;
        $d = json_decode(file_get_contents($p), true);

        return $d['exported_at'] ?? null;
    }

    /** Whether the committed snapshot is newer than what's been imported here. */
    private function syncInfo(): array
    {
        $fileAt = $this->fileExportedAt();
        $available = $fileAt !== null && $fileAt !== ReportSetting::get('synced_at');

        $newDays = 0;
        $changedDays = 0;
        if ($available) {
            $data = json_decode(file_get_contents($this->exportFile()), true);
            $existingDates = [];
            $existingSig = [];
            foreach (ReportDay::all() as $r) {
                $dt = $r->date->format('Y-m-d');
                $existingDates[$dt] = true;
                $existingSig[$r->site . '|' . $dt] = $this->daySignature($r->revenue, $r->impressions, $r->total_ad_requests, $r->analytics, $r->impressions_sold);
            }
            // Count distinct calendar dates that are new (absent here) or changed.
            $newDates = [];
            $changedDates = [];
            foreach ($data['days'] ?? [] as $d) {
                $dt = $d['date'];
                $key = $d['site'] . '|' . $dt;
                $sig = $this->daySignature($d['revenue'] ?? [], $d['impressions'] ?? null, $d['total_ad_requests'] ?? 0, $d['analytics'] ?? null, $d['impressions_sold'] ?? 0);
                if (! isset($existingDates[$dt])) $newDates[$dt] = true;
                elseif (! isset($existingSig[$key]) || $existingSig[$key] !== $sig) $changedDates[$dt] = true;
            }
            foreach (array_keys($newDates) as $dt) unset($changedDates[$dt]);
            $newDays = count($newDates);
            $changedDays = count($changedDates);
        }

        return [
            'available' => $available,
            'exportedAt' => $fileAt,
            'syncedAt' => ReportSetting::get('synced_at'),
            'newDays' => $newDays,
            'changedDays' => $changedDays,
            'pending' => $newDays + $changedDays,
        ];
    }

    /** Order-stable, float-rounded signature of a day's values for change detection. */
    private function daySignature($revenue, $impressions, $tar, $analytics, $sold): string
    {
        $norm = function ($v) use (&$norm) {
            if (is_array($v)) {
                ksort($v);
                return array_map($norm, $v);
            }
            return is_float($v) ? round($v, 4) : $v;
        };

        return json_encode([$norm($revenue), $norm($impressions), (int) $tar, $norm($analytics), (int) $sold]);
    }

    /** Import the committed snapshot into this machine's DB. */
    public function sync()
    {
        $fileAt = $this->fileExportedAt();
        if ($fileAt === null) return redirect()->route('reporting');

        Artisan::call('reporting:import');
        ReportSetting::put('synced_at', $fileAt);

        return redirect()->route('reporting')->with('status', 'reporting-synced');
    }

    /** Save the user-editable list of report-source links. */
    public function links(Request $request)
    {
        $data = $request->validate([
            'links' => ['present', 'array'],
            'links.*.label' => ['required', 'string', 'max:80'],
            'links.*.url' => ['required', 'url', 'max:2048'],
        ]);

        ReportSetting::put('report_links', array_values($data['links']));

        // Keep the committed snapshot in sync with link changes too (skipped in tests).
        if (! app()->runningUnitTests()) {
            Artisan::call('reporting:export');
        }

        return redirect()->route('reporting');
    }

    public function index()
    {
        return Inertia::render('Reporting/Index', array_merge($this->baseProps(), [
            'lastRun' => ReportSetting::get('last_run'),
            'verifyResult' => session('verifyResult'),
            'verifyError' => session('verifyError'),
        ]));
    }

    /** Handle an upload run, then re-render with the refreshed store. */
    public function process(Request $request)
    {
        $request->validate(['files' => 'required|array', 'files.*' => 'file']);

        $files = collect($request->file('files'))
            ->map(fn ($f) => ['name' => $f->getClientOriginalName(), 'path' => $f->getRealPath()])
            ->all();

        $result = ReportProcessor::process($files, $this->uploadsDir());

        // Persist the run's missing-files list so the alert is reliable and survives
        // reloads (no fragile session flash), until the next upload replaces it.
        ReportSetting::put('last_run', [
            'at' => now()->toDateTimeString(),
            'missing' => $this->missingFromTypes($result['fileTypes']),
        ]);

        // Refresh the committed snapshot so the new data is ready to commit + sync
        // to other machines (also re-stamps synced_at for this machine). Skipped in
        // tests so the suite never overwrites the real committed data file.
        if (! app()->runningUnitTests()) {
            Artisan::call('reporting:export');
        }

        return redirect()->route('reporting');
    }

    /** Required partner files not present in an upload run (Adhese broken out per site). */
    private function missingFromTypes(array $fileTypes): array
    {
        $required = [
            'adform' => 'Adform', 'gam' => 'GAM', 'gam_f1m' => 'GAM F1M', 'ogury' => 'Ogury',
            'seedtag' => 'SeedTag', 'showheroes' => 'Showheroes', 'teads' => 'Teads', 'analytics' => 'Analytics',
            'adhese_f1' => 'Adhese (F1)', 'adhese_tg' => 'Adhese (TopGear)', 'adhese_fl' => 'Adhese (Festileaks)',
        ];
        $uploaded = array_values($fileTypes);
        $adhese = [];
        foreach ($fileTypes as $name => $type) {
            if ($type !== 'adhese') continue;
            $n = mb_strtolower($name);
            if (str_contains($n, 'adhese tg') || str_contains($n, 'adhese topgear')) $adhese['adhese_tg'] = true;
            elseif (str_contains($n, 'adhese fl') || str_contains($n, 'adhese festileaks')) $adhese['adhese_fl'] = true;
            else $adhese['adhese_f1'] = true;
        }

        $missing = [];
        foreach ($required as $key => $label) {
            $present = str_starts_with($key, 'adhese_') ? isset($adhese[$key]) : in_array($key, $uploaded, true);
            if (! $present) $missing[] = $label;
        }

        return $missing;
    }

    /** Manually set F1Maximaal's Adhese impressions for a day (no file source). */
    public function saveAdhese(Request $request)
    {
        $data = $request->validate(['dateKey' => 'required|string', 'adhese' => 'nullable']);

        $row = ReportDay::firstWhere(['site' => 'f1maximaal', 'date' => $data['dateKey']]);
        if (! $row) return back()->with('error', 'Date not found');

        $imp = $row->impressions ?? [];
        $adhese = $data['adhese'];
        $imp['adhese'] = ($adhese === null || $adhese === '') ? null : (int) $adhese;

        $sum = 0;
        foreach (['seedtag', 'teads', 'showheroes', 'gam', 'adform', 'ogury', 'outbrain', 'adhese', 'preferredDeals'] as $p) {
            $sum += (int) ($imp[$p] ?? 0);
        }
        $row->update(['impressions' => $imp, 'impressions_sold' => $sum]);

        // Refresh the committed snapshot so the entered impressions land in the JSON
        // (and sync to other machines), mirroring the upload flow. Skipped in tests.
        if (! app()->runningUnitTests()) {
            Artisan::call('reporting:export');
        }

        return redirect()->route('reporting');
    }

    /** Batch-set Adhese impressions for multiple F1Maximaal days at once. */
    public function saveAdheseBatch(Request $request)
    {
        $data = $request->validate([
            'entries'             => 'required|array',
            'entries.*.dateKey'   => 'required|string',
            'entries.*.adhese'    => 'nullable',
        ]);

        // Fetch every affected day in one query, keyed by date, instead of a
        // SELECT per entry.
        $rowsByDate = ReportDay::where('site', 'f1maximaal')
            ->whereIn('date', array_column($data['entries'], 'dateKey'))
            ->get()
            ->keyBy(fn ($r) => $r->date->format('Y-m-d'));

        foreach ($data['entries'] as $entry) {
            $row = $rowsByDate->get($entry['dateKey']);
            if (! $row) continue;

            $imp    = $row->impressions ?? [];
            $adhese = $entry['adhese'];
            $imp['adhese'] = ($adhese === null || $adhese === '') ? null : (int) $adhese;

            $sum = 0;
            foreach (['seedtag', 'teads', 'showheroes', 'gam', 'adform', 'ogury', 'outbrain', 'adhese', 'preferredDeals'] as $p) {
                $sum += (int) ($imp[$p] ?? 0);
            }
            $row->update(['impressions' => $imp, 'impressions_sold' => $sum]);
        }

        // Refresh the committed snapshot so the entered impressions land in the JSON
        // (and sync to other machines), mirroring the upload flow. Skipped in tests.
        if (! app()->runningUnitTests()) {
            Artisan::call('reporting:export');
        }

        return redirect()->route('reporting');
    }

    /** Verify the F1Maximaal monthly Planetnine report. */
    public function verify(Request $request)
    {
        $request->validate(['file' => 'required|file']);
        try {
            $rows = Verifier::monthly(ReportStore::load(), $request->file('file')->getRealPath());

            return $this->renderWithVerify([
                'rows' => $rows, 'site' => 'f1maximaal', 'siteName' => Reporting::SITES['f1maximaal']['name'],
            ], null);
        } catch (\Throwable $e) {
            return $this->renderWithVerify(null, $e->getMessage());
        }
    }

    /** Verify a weekly revenue report (TopGear / Horses / Festileaks). */
    public function verifyWeekly(Request $request)
    {
        $request->validate(['file' => 'required|file', 'site' => 'required|string']);
        $siteId = $request->input('site');
        if (! isset(Reporting::SITES[$siteId])) return $this->renderWithVerify(null, 'Unknown site');

        try {
            $result = Verifier::weekly(ReportStore::load(), $request->file('file')->getRealPath(), $siteId);

            return $this->renderWithVerify(array_merge(['site' => $siteId], $result), null);
        } catch (\Throwable $e) {
            return $this->renderWithVerify(null, $e->getMessage());
        }
    }

    /**
     * Render the reporting page directly with a verify outcome, instead of
     * redirect()->with() + a follow-up GET. A redirect remounts the Vue page
     * component — replaying the welcome splash and losing the active tab —
     * just to fix up the address bar. Rendering inline avoids all of that;
     * the frontend corrects the address bar itself via history.replaceState
     * once the response lands (see runVerify()'s onSuccess in Index.vue).
     */
    private function renderWithVerify(?array $verifyResult, ?string $verifyError)
    {
        return Inertia::render('Reporting/Index', array_merge($this->baseProps(), [
            'lastRun' => ReportSetting::get('last_run'),
            'verifyResult' => $verifyResult,
            'verifyError' => $verifyError,
        ]));
    }

    /** Update config (e.g. oguryRate). */
    public function config(Request $request)
    {
        $data = $request->validate([
            'oguryRate' => 'nullable|numeric',
            'reminderDay' => 'nullable|integer|between:0,6',
            'rpmAmber' => 'nullable|numeric|min:0',
            'rpmRed' => 'nullable|numeric|min:0',
            'oguryOldFormat' => 'nullable|boolean',
            'filePatterns' => 'nullable|array',
            'filePatterns.*' => 'nullable|string|max:255',
        ]);
        if (array_key_exists('oguryRate', $data) && $data['oguryRate'] !== null) {
            ReportSetting::put('oguryRate', (float) $data['oguryRate']);
        }
        if (array_key_exists('reminderDay', $data) && $data['reminderDay'] !== null) {
            ReportSetting::put('reminder_day', (int) $data['reminderDay']);
        }
        if (array_key_exists('rpmAmber', $data) && $data['rpmAmber'] !== null) {
            ReportSetting::put('rpm_amber', (float) $data['rpmAmber']);
        }
        if (array_key_exists('rpmRed', $data) && $data['rpmRed'] !== null) {
            ReportSetting::put('rpm_red', (float) $data['rpmRed']);
        }
        if (array_key_exists('oguryOldFormat', $data) && $data['oguryOldFormat'] !== null) {
            ReportSetting::put('ogury_old_format', (bool) $data['oguryOldFormat']);
        }
        if (array_key_exists('filePatterns', $data) && is_array($data['filePatterns'])) {
            // Persist only the known configurable partner keys, trimmed.
            $clean = [];
            foreach (array_keys(Reporting::DEFAULT_FILE_PATTERNS) as $key) {
                if (isset($data['filePatterns'][$key]) && is_string($data['filePatterns'][$key])) {
                    $clean[$key] = trim($data['filePatterns'][$key]);
                }
            }
            ReportSetting::put('file_patterns', $clean);
        }

        // Refresh the committed snapshot so setting changes (rpm thresholds, ogury
        // rate, file patterns, …) land in the JSON and sync to other machines,
        // mirroring the upload flow. Skipped in tests.
        if (! app()->runningUnitTests()) {
            Artisan::call('reporting:export');
        }

        return redirect()->route('reporting')->with('success', 'Settings saved');
    }

    /** Download the reports ZIP (optionally a date range + file subset). */
    public function download(Request $request)
    {
        $requested = $request->query('files') ? array_map('trim', explode(',', $request->query('files'))) : null;
        try {
            $buf = ZipBuilder::build(
                ReportStore::load(),
                $this->uploadsDir(),
                $requested,
                $request->query('from'),
                $request->query('to'),
                (bool) ReportSetting::get('ogury_old_format', false),
            );
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 404);
        }

        return response($buf)
            ->header('Content-Type', 'application/zip')
            ->header('Content-Disposition', 'attachment; filename="F1Maximaal Reports.zip"');
    }

    /** Export the dashboard table (per-day revenue + impressions) as csv/xlsx/json. */
    public function exportTable(Request $request)
    {
        $data = $request->validate([
            'site' => ['required', 'string'],
            'format' => ['required', 'in:csv,xlsx,json'],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d'],
        ]);
        if (! isset(Reporting::SITES[$data['site']])) {
            return response()->json(['error' => 'Unknown site'], 404);
        }

        $export = TableExporter::data(ReportStore::load(), $data['site'], $data['from'] ?? null, $data['to'] ?? null);

        $range = ($data['from'] ?? 'all') . '_' . ($data['to'] ?? 'all');
        $base = str_replace(' ', '_', $export['siteName']) . " reporting {$range}";

        [$body, $mime, $ext] = match ($data['format']) {
            'csv' => [TableExporter::csv($export), 'text/csv', 'csv'],
            'json' => [TableExporter::json($export), 'application/json', 'json'],
            'xlsx' => [TableExporter::xlsx($export), 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'xlsx'],
        };

        return response($body)
            ->header('Content-Type', $mime)
            ->header('Content-Disposition', "attachment; filename=\"{$base}.{$ext}\"");
    }

    /** Delete a single stored day. */
    public function destroy(string $siteId, string $dateKey)
    {
        $deleted = ReportDay::where(['site' => $siteId, 'date' => $dateKey])->delete();
        if (! $deleted) return back()->with('error', 'Not found');

        return redirect()->route('reporting');
    }

    /** JSON list of files available for download (used by the ZIP modal, if needed). */
    public function uploadFiles()
    {
        return response()->json(['files' => ZipBuilder::availableFiles($this->uploadsDir())]);
    }
}
