<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ColorPalette;
use Inertia\Inertia;

class ColorPaletteController extends Controller
{
    public function index()
    {
        $colorPalettes = ColorPalette::all();
        return Inertia::render('ColorPalettes/Index', [
            'colorPalettes' => $colorPalettes,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'primary' => 'required|string|max:7',
            'secondary' => 'required|string|max:7',
            'tertiary' => 'required|string|max:7',
            'quaternary' => 'required|string|max:7',
            'status' => 'boolean',
        ]);

        ColorPalette::create($data);

        return redirect()->route('color-palettes')->with('success', 'Color palette created successfully.');
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'primary' => 'required|string|max:7',
            'secondary' => 'required|string|max:7',
            'tertiary' => 'required|string|max:7',
            'quaternary' => 'required|string|max:7',
            'status' => 'boolean',
        ]);

        $palette = ColorPalette::findOrFail($id);

        // If status is being set to 1 and the current palette is not already active
        if ($data['status'] && !$palette->status) {
            $activeCount = ColorPalette::where('status', 1)->count();

            if ($activeCount >= 6) {
                return back()->withErrors(['status' => 'You can only have a maximum of 6 active color palettes.']);
            }
        }

        $palette->update($data);

        return redirect()->route('color-palettes')->with('success', 'Color palette updated successfully.');
    }

    public function destroy($id)
    {
        $palette = ColorPalette::findOrFail($id);
        $palette->delete();

        return redirect()->route('color-palettes')->with('success', 'Color palette deleted successfully.');
    }
}
