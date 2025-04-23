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

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'website' => 'required|url',
            'preview_url' => 'nullable|url',
            'logo' => 'required|image|mimes:jpeg,png,jpg,svg|max:2048', // Validate image file
            'brand_color' => 'required|string|max:7',
        ]);

        // ✅ Handle logo upload
        if ($request->hasFile('logo')) {
            $file = $request->file('logo');
            $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
            $path = public_path('clients');

            if (!File::exists($path)) {
                File::makeDirectory($path, 0755, true);
            }

            $file->move($path, $filename);
            $validated['logo'] = 'clients/' . $filename; // Save relative path
        }

        Client::create($validated);

        return redirect()->route('clients.index')->with('success', 'Client created successfully.');
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
