<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\TourGuide;

class PreviewTourGuideController extends Controller
{
    public function index()
    {
        // Get or create the tour guide setting
        $tourGuide = TourGuide::firstOrCreate(
            ['id' => 1],
            ['is_active' => true]
        );

        return Inertia::render('TourGuide/Index', [
            'tourGuide' => $tourGuide,
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'is_active' => 'required|boolean',
        ]);

        $tourGuide = TourGuide::firstOrCreate(['id' => 1]);
        $tourGuide->update([
            'is_active' => $request->is_active,
        ]);

        return redirect()->back()->with('success', 'Tour guide status updated successfully!');
    }
}
