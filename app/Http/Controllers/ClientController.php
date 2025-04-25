<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;

class ClientController extends Controller
{
    public function index()
    {
        $clients = Client::latest()->paginate(10);
        return Inertia::render('Clients/Index', [
            'clients' => $clients
        ]);
    }

    public function create()
    {
        return Inertia::render('Clients/Create');
    }

    public function edit($id)
    {
        $client = Client::findOrFail($id);
        return Inertia::render('Clients/Edit', [
            'client' => $client
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'website' => 'required|url',
            'preview_url' => 'nullable|url',
            'logo' => 'nullable|image|max:2048',
            'brand_color' => 'nullable|string|max:20',
        ]);

        $filename = null;

        if ($request->hasFile('logo')) {
            $logo = $request->file('logo');
            $filename = time() . '_' . $logo->getClientOriginalName();
            $logo->move(public_path('logo'), $filename);
        }

        Client::create([
            'name' => $validated['name'],
            'website' => $validated['website'],
            'preview_url' => $validated['preview_url'],
            'logo' => $filename,
            'brand_color' => $validated['brand_color'],
        ]);

        return redirect()->route('clients-index')->with('success', 'Client created successfully.');
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'website' => 'required|url',
            'preview_url' => 'nullable|url',
            'logo' => 'nullable|image|max:2048',
            'brand_color' => 'nullable|string|max:20',
        ]);

        $client = Client::findOrFail($id);
        $filename = $client->logo;

        if ($request->hasFile('logo')) {
            // Delete the old logo file if it exists
            if ($client->logo && file_exists(public_path($client->logo))) {
                unlink(public_path($client->logo));
            }

            $logo = $request->file('logo');
            $filename = time() . '_' . $logo->getClientOriginalName();
            $logo->move(public_path('logo'), $filename);
        }

        $client->update([
            'name' => $validated['name'],
            'website' => $validated['website'],
            'preview_url' => $validated['preview_url'],
            'logo' => $filename,
            'brand_color' => $validated['brand_color'],
        ]);
    }

    public function destroy($id)
    {
        $client = Client::findOrFail($id);

        // Optionally delete the logo file
        if ($client->logo && file_exists(public_path($client->logo))) {
            unlink(public_path($client->logo));
        }

        $client->delete();

        return redirect()->route('clients')->with('success', 'Client deleted successfully.');
    }
}
