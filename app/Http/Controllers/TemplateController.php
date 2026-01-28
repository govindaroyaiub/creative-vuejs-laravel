<?php

namespace App\Http\Controllers;

use App\Models\Template;
use Illuminate\Http\Request;
use ZipArchive;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Http\UploadedFile;


class TemplateController extends Controller
{
    public function index()
    {
        $search = request()->query('search');

        $templates = Template::when($search, function ($q, $search) {
            $q->where('name', 'like', "%{$search}%");
        })->orderBy('created_at', 'desc')->paginate(10)->withQueryString();

        return Inertia::render('Templates/Index', [
            'templates' => $templates,
            'filters' => request()->only('search'),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'file' => ['nullable', 'file', 'mimes:zip'],
            'url' => ['nullable', 'url', 'max:2048'],
            'name' => ['nullable', 'string', 'max:255'],
        ]);

        // Must provide either a file or a URL
        if (! $request->hasFile('file') && ! $request->filled('url')) {
            return redirect()->back()->with('error', 'Please provide a ZIP file or an external URL.');
        }

        $data = [];

        if ($request->filled('url')) {
            $data['url'] = $request->input('url');
            $data['name'] = $request->input('name') ?: $request->input('url');
        }

        if ($request->hasFile('file')) {
            /** @var UploadedFile $file */
            $file = $request->file('file');
            $originalName = $file->getClientOriginalName();
            $filename = Str::random(12) . '_' . time() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('templates', $filename, 'public');

            $data['file_path'] = $path;
            $data['file_name'] = $originalName;
            $data['name'] = $request->input('name') ?: pathinfo($originalName, PATHINFO_FILENAME);
        }

        $template = Template::create($data);

        return redirect()->route('templates.index')->with('success', 'Template saved.');
    }

    public function update(Request $request, Template $template)
    {
        $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'file' => ['nullable', 'file', 'mimes:zip'],
            'url' => ['nullable', 'url', 'max:2048'],
        ]);

        // If a URL is provided, clear file fields
        if ($request->filled('url')) {
            // delete old file if exists
            if ($template->file_path && Storage::disk('public')->exists($template->file_path)) {
                Storage::disk('public')->delete($template->file_path);
            }

            $template->url = $request->input('url');
            $template->file_path = null;
            $template->file_name = null;
        }

        if ($request->hasFile('file')) {
            // delete old file
            if ($template->file_path && Storage::disk('public')->exists($template->file_path)) {
                Storage::disk('public')->delete($template->file_path);
            }

            $file = $request->file('file');
            $originalName = $file->getClientOriginalName();
            $filename = Str::random(12) . '_' . time() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('templates', $filename, 'public');

            $template->file_path = $path;
            $template->file_name = $originalName;
            $template->url = null;
        }

        if ($request->filled('name')) {
            $template->name = $request->input('name');
        }

        $template->save();

        return redirect()->route('templates.index')->with('success', 'Template updated.');
    }

    public function destroy(Template $template)
    {
        if ($template->file_path && Storage::disk('public')->exists($template->file_path)) {
            Storage::disk('public')->delete($template->file_path);
        }

        $template->delete();

        return redirect()->route('templates.index')->with('success', 'Template deleted.');
    }

    public function download(Template $template)
    {
        // If template is a link, redirect to external URL
        if ($template->url) {
            return redirect()->away($template->url);
        }

        if (! $template->file_path || ! Storage::disk('public')->exists($template->file_path)) {
            return redirect()->back()->with('error', 'File not found.');
        }

        return Storage::disk('public')->download($template->file_path, $template->file_name);
    }
}
