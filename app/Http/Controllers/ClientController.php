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
            // Required: `clients.logo` is NOT NULL and the preview header
            // renders it (PreviewTopBar reads `/logos/{headerLogo.logo}`), so a
            // client without one would ship a broken image to the client-facing
            // page. Validating it here turns what was a 500 from the database
            // into a normal 422 the user can read and act on.
            'logo' => 'required|image|max:2048',
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
            // `validate()` omits absent optional keys entirely, so reading
            // this directly threw "Undefined array key" when the field was
            // not submitted at all.
            'preview_url' => $validated['preview_url'] ?? null,
            // Null when no logo was picked; the column is nullable and the UI
            // renders the logo behind a `v-if`.
            'logo' => $filename,
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
            // Nullable here on purpose: omitting the file means "keep the
            // existing logo", which is what `$filename = $client->logo` below
            // does. A client can never end up without one.
            'logo' => 'nullable|image|max:2048',
            // Required, not nullable: the column is NOT NULL and store()
            // already demands one. Accepting null here passed validation and
            // then failed the update with "Column 'color_palette_id' cannot be
            // null" — the same mismatch that made `logo` fail on create.
            'color_palette_id' => 'required|exists:color_palettes,id',
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
            // `validate()` omits absent optional keys entirely, so reading
            // this directly threw "Undefined array key" when the field was
            // not submitted at all.
            'preview_url' => $validated['preview_url'] ?? null,
            'logo' => $filename,
            'color_palette_id' => $validated['color_palette_id'],
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
