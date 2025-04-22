<?php

namespace App\Http\Controllers;

use App\Models\Social;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SocialController extends Controller
{
    public function index()
    {
        $socials = Social::orderBy('name')->paginate(10);

        return Inertia::render('Socials/Index', [
            'socials' => $socials,
        ]);
    }

    public function create() {}

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        Social::create([
            'name' => $request->name,
        ]);

        return redirect()->route('socials')->with('success', 'Social created successfully.');
    }

    public function edit($id) {}

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $social = Social::findOrFail($id);
        $social->update(['name' => $request->name]);

        return redirect()->route('socials')->with('success', 'Social updated successfully.');
    }

    public function destroy($id)
    {
        $social = Social::findOrFail($id);
        $social->delete();

        return redirect()->route('socials')->with('success', 'Social deleted successfully.');
    }
}
