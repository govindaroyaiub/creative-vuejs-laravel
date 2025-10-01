<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ColorPalette;
use Inertia\Inertia;

class ColorPaletteController extends Controller
{
    public function index(Request $request)
    {
        $query = ColorPalette::query();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('primary', 'like', "%{$search}%")
                    ->orWhere('secondary', 'like', "%{$search}%")
                    ->orWhere('tertiary', 'like', "%{$search}%")
                    ->orWhere('quaternary', 'like', "%{$search}%")
                    ->orWhere('quinary', 'like', "%{$search}%")
                    ->orWhere('senary', 'like', "%{$search}%")
                    ->orWhere('septenary', 'like', "%{$search}%");
            });
        }

        $colorPalettes = $query->orderBy('created_at', 'desc')->get();

        return Inertia::render('ColorPalettes/Index', [
            'colorPalettes' => $colorPalettes,
            'search' => $search,
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
            'quinary' => 'nullable|string|max:7',
            'senary' => 'nullable|string|max:7',
            'septenary' => 'nullable|string|max:7',
            'feedbackTab_inactive_image' => 'nullable|image|max:2048',
            'feedbackTab_active_image' => 'nullable|image|max:2048',
            'rightSideTab_feedback_description_image' => 'nullable|image|max:2048',
            'rightSideTab_color_palette_image' => 'nullable|image|max:2048',
            'header_image' => 'nullable|image|max:2048',
        ]);

        // Set status to 0 by default
        $data['status'] = 0;

        foreach (
            [
                'feedbackTab_inactive_image',
                'feedbackTab_active_image',
                'rightSideTab_feedback_description_image',
                'rightSideTab_color_palette_image',
                'header_image'
            ] as $field
        ) {
            if ($request->hasFile($field)) {
                $file = $request->file($field);
                $filename = uniqid() . '_' . $file->getClientOriginalName();
                $file->move(public_path('uploads/colorPalette'), $filename);
                $data[$field] = 'uploads/colorPalette/' . $filename;
            }
        }

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
            'quinary' => 'nullable|string|max:7',
            'senary' => 'nullable|string|max:7',
            'septenary' => 'nullable|string|max:7',
            'feedbackTab_inactive_image' => 'nullable|image|max:2048',
            'feedbackTab_active_image' => 'nullable|image|max:2048',
            'rightSideTab_feedback_description_image' => 'nullable|image|max:2048',
            'rightSideTab_color_palette_image' => 'nullable|image|max:2048',
            'header_image' => 'nullable|image|max:2048',
        ]);

        $palette = ColorPalette::findOrFail($id);

        foreach (
            [
                'feedbackTab_inactive_image',
                'feedbackTab_active_image',
                'rightSideTab_feedback_description_image',
                'rightSideTab_color_palette_image',
                'header_image'
            ] as $field
        ) {
            if ($request->hasFile($field)) {
                // Delete old image if exists
                if ($palette[$field]) {
                    $oldPath = public_path($palette[$field]);
                    if (file_exists($oldPath)) {
                        @unlink($oldPath);
                    }
                }
                // Store new image
                $file = $request->file($field);
                $filename = uniqid() . '_' . $file->getClientOriginalName();
                $file->move(public_path('uploads/colorPalette'), $filename);
                $data[$field] = 'uploads/colorPalette/' . $filename;
            }
        }

        $palette->update($data);

        return redirect()->route('color-palettes')->with('success', 'Color palette updated successfully.');
    }

    public function toggleStatus(Request $request, $id)
    {
        $data = $request->validate([
            'status' => 'required|boolean',
        ]);

        $palette = ColorPalette::findOrFail($id);

        // If status is being set to 1 and the current palette is not already active
        if ($data['status'] && !$palette->status) {
            $activeCount = ColorPalette::where('status', 1)->count();

            if ($activeCount >= 6) {
                return back()->withErrors(['status' => 'You can only have a maximum of 6 active color palettes.']);
            }
        }

        $palette->update(['status' => $data['status']]);

        return redirect()->route('color-palettes')->with('success', 'Color palette status updated successfully.');
    }

    public function destroy($id)
    {
        $palette = ColorPalette::findOrFail($id);

        foreach (
            [
                'feedbackTab_inactive_image',
                'feedbackTab_active_image',
                'rightSideTab_feedback_description_image',
                'rightSideTab_color_palette_image',
                'header_image'
            ] as $field
        ) {
            if ($palette[$field]) {
                $filePath = public_path($palette[$field]);
                if (file_exists($filePath)) {
                    @unlink($filePath);
                }
            }
        }

        $palette->delete();

        return redirect()->route('color-palettes')->with('success', 'Color palette deleted successfully.');
    }
}
