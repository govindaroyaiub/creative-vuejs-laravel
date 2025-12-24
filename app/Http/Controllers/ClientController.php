<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\ColorPalette;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;

class ClientController extends Controller
{
    public function index(Request $request)
    {
        $query = Client::with('colorPalette');

        if ($search = $request->input('search')) {
            $query->where('name', 'like', "%{$search}%")
                ->orWhere('website', 'like', "%{$search}%")
                ->orWhere('preview_url', 'like', "%{$search}%");
        }

        $clients = $query->latest()->paginate(15)->withQueryString();
        $colorPalettes = ColorPalette::where('status', 1)->get(['id', 'name', 'primary']);

        return Inertia::render('Clients/Index', [
            'clients' => $clients,
            'colorPalettes' => $colorPalettes,
            'search' => $search,
        ]);
    }

    public function create() {}

    public function edit($id) {}

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'website' => 'required|url',
            'preview_url' => 'nullable|url',
            'logo' => 'nullable|image|max:2048',
            'color_palette_id' => 'required|exists:color_palettes,id',
        ]);

        $filename = null;

        if ($request->hasFile('logo')) {
            $logo = $request->file('logo');
            $filename = time() . '_' . $logo->getClientOriginalName();
            $logo->move(public_path('logos'), $filename);
        }

        $palette = ColorPalette::find($validated['color_palette_id']);

        Client::create([
            'name' => $validated['name'],
            'website' => $validated['website'],
            'preview_url' => $validated['preview_url'],
            'logo' => $filename,
            'brand_color' => $palette->primary,
            'color_palette_id' => $palette->id,
        ]);

        return redirect()->route('clients')->with('success', 'Client created successfully.');
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'website' => 'required|url',
            'preview_url' => 'nullable|url',
            'logo' => 'nullable|image|max:2048',
            'color_palette_id' => 'nullable|exists:color_palettes,id',
        ]);

        $client = Client::findOrFail($id);
        $filename = $client->logo;

        if ($request->hasFile('logo')) {
            // Delete old logo if it exists
            $oldLogoPath = public_path('logos/' . $client->logo);
            if ($client->logo && file_exists($oldLogoPath)) {
                unlink($oldLogoPath);
            }

            $logo = $request->file('logo');
            $filename = time() . '_' . $logo->getClientOriginalName();
            $logo->move(public_path('logos'), $filename);
        }

        $client->update([
            'name' => $validated['name'],
            'website' => $validated['website'],
            'preview_url' => $validated['preview_url'],
            'logo' => $filename,
            'color_palette_id' => $validated['color_palette_id'] ?? null,
        ]);

        return redirect()->route('clients')->with('success', 'Client updated successfully.');
    }

    public function destroy($id)
    {
        $client = Client::findOrFail($id);

        // Optionally delete the logo file
        if ($client->logo && file_exists(public_path('logos/' . $client->logo))) {
            unlink(public_path('logos/' . $client->logo));
        }

        $client->delete();

        return redirect()->route('clients')->with('success', 'Client deleted successfully.');
    }
}
