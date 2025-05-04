<?php

namespace App\Http\Controllers;

use App\Models\Preview;
use App\Models\Client;
use App\Models\User;
use App\Models\BannerSize;
use App\Models\ColorPalette;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PreviewController extends Controller
{
    public function index()
    {
        $previews = Preview::with(['client', 'uploader', 'colorPalette', 'versions.subVersions'])
            ->orderBy('created_at', 'asc')
            ->paginate(10)
            ->through(function ($preview) {
                $preview->team_users = User::whereIn('id', $preview->team_members)->get(['id', 'name']);
                return $preview;
            });

        return Inertia::render('Previews/Index', [
            'previews' => $previews,
            'clients' => Client::orderBy('name')->get(['id', 'name']),
            'users' => User::orderBy('name')->get(['id', 'name']),
            'colorPalettes' => ColorPalette::orderBy('name')->get(['id', 'name']),
            'bannerSizes' => BannerSize::orderBy('width', 'ASC')
                ->orderBy('height', 'ASC')
                ->get(['id', 'width', 'height'])
                ->map(function ($size) {
                    $size->name = "{$size->width}x{$size->height}";
                    return $size;
                }),
            'auth' => ['user' => auth()->user()],
        ]);
    }

    public function create()
    {
        return Inertia::render('Previews/Create', [
            'clients' => Client::orderBy('name')->get(['id', 'name']),
            'users' => User::orderBy('name')->get(['id', 'name']),
            'colorPalettes' => ColorPalette::orderBy('name')->get(['id', 'name']),
            'auth' => ['user' => auth()->user()],
        ]);
    }

    public function store(Request $request)
    {
        dd($request->all());
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'client_id' => 'required|exists:clients,id',
            'team_members' => 'required|array',
            'team_members.*' => 'exists:users,id',
            'uploader_id' => 'required|exists:users,id',
            'color_palette_id' => 'nullable|exists:color_palettes,id',
        ]);

        Preview::create($validated);

        return redirect()->route('previews.index')->with('success', 'Preview created.');
    }

    public function show(Preview $preview)
    {
        $preview->load(['client', 'uploader', 'colorPalette', 'versions.subVersions']);
        return inertia('Previews/Show', [
            'preview' => $preview,
        ]);
    }

    public function edit(Preview $preview)
    {
        return inertia('Previews/Edit', [
            'preview' => $preview,
            'clients' => Client::all(),
            'users' => User::all(),
            'palettes' => ColorPalette::all(),
        ]);
    }

    public function update(Request $request, Preview $preview)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'client_id' => 'required|exists:clients,id',
            'team_members' => 'required|array',
            'team_members.*' => 'exists:users,id',
            'uploader_id' => 'required|exists:users,id',
            'color_palette_id' => 'nullable|exists:color_palettes,id',
        ]);

        $preview->update($validated);

        return redirect()->route('previews-index')->with('success', 'Preview updated.');
    }

    public function destroy(Preview $preview)
    {
        $preview->delete();

        return redirect()->route('previews-index')->with('success', 'Preview deleted.');
    }
}
