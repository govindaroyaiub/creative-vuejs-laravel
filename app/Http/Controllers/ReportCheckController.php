<?php

namespace App\Http\Controllers;

use App\Models\ReportCheck;
use App\Services\ReportCheck\ReportCheckRunner;
use App\Services\ReportCheck\SourceFileMatcher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class ReportCheckController extends Controller
{
    public function index()
    {
        $checks = ReportCheck::query()
            ->with('uploader:id,name,email')
            ->withCount(['issues as major_count' => fn($q) => $q->where('severity', 'major')])
            ->withCount(['issues as minor_count' => fn($q) => $q->where('severity', 'minor')])
            ->orderByDesc('created_at')
            ->paginate(20);

        return Inertia::render('ReportChecks/Index', [
            'checks'     => $checks,
            'publishers' => array_keys(config('report_publishers', [])),
        ]);
    }

    public function create()
    {
        return Inertia::render('ReportChecks/Create', [
            'publishers'  => config('report_publishers'),
            'source_keys' => SourceFileMatcher::SOURCE_KEYS,
        ]);
    }

    public function store(Request $request, ReportCheckRunner $runner)
    {
        $validated = $request->validate([
            'publisher'  => ['required', 'string', 'in:' . implode(',', array_keys(config('report_publishers', [])))],
            'files'      => ['required', 'array', 'min:10', 'max:10'],
            'files.*'    => ['required', 'file', 'max:25600'], // 25 MB each
        ]);

        $detected = [];
        $unknown = [];
        foreach ($request->file('files', []) as $upload) {
            $key = SourceFileMatcher::detect($upload->getClientOriginalName());
            if ($key === null) {
                $unknown[] = $upload->getClientOriginalName();
                continue;
            }
            if (isset($detected[$key])) {
                throw ValidationException::withMessages([
                    'files' => "Two files matched source '{$key}': {$detected[$key]['filename']} and {$upload->getClientOriginalName()}",
                ]);
            }
            $detected[$key] = [
                'path'     => $upload->getRealPath(),
                'filename' => $upload->getClientOriginalName(),
                'sha256'   => hash_file('sha256', $upload->getRealPath()),
            ];
        }

        if (!empty($unknown)) {
            throw ValidationException::withMessages([
                'files' => 'Unrecognized filename(s): ' . implode(', ', $unknown),
            ]);
        }
        $missing = SourceFileMatcher::missing($detected);
        if (!empty($missing)) {
            throw ValidationException::withMessages([
                'files' => 'Missing source(s): ' . implode(', ', $missing),
            ]);
        }

        $check = $runner->run($detected, $validated['publisher'], Auth::id());

        return redirect()->route('report-checks.show', $check)->with('success', 'Report check completed.');
    }

    public function show(ReportCheck $check)
    {
        $check->load(['uploader:id,name,email', 'files', 'issues', 'revenues']);

        return Inertia::render('ReportChecks/Show', [
            'check' => $check,
        ]);
    }

    public function destroy(ReportCheck $check)
    {
        $check->delete();
        return redirect()->route('report-checks.index')->with('success', 'Report check deleted.');
    }
}
