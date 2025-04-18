<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use App\Models\BannerSize;

class BannerSizeController extends Controller
{
    public function index()
    {
        $bannerSizes = BannerSize::orderBy('width', 'asc')->orderBy('height', 'ASC')->paginate(10);
        return Inertia::render('BannerSizes/Index', [
            'bannerSizes' => $bannerSizes,
        ]);
    }

    public function create()
    {
        return Inertia::render('BannerSizes/Create', [
            'flash' => session('success'), // Pass flash message explicitly
        ]);
    }

    public function edit($id)
    {
        $bannerSize = BannerSize::findOrFail($id);
        return Inertia::render('BannerSizes/Edit', [
            'bannerSize' => $bannerSize,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'width' => 'required|integer|min:1',
            'height' => 'required|integer|min:1',
        ]);

        $exists = BannerSize::where('width', $validated['width'])
            ->where('height', $validated['height'])
            ->exists();

        if ($exists) {
            return Redirect::route('banner-sizes-create')->with('success', 'Sorry! This banner size already exist!');
        }

        BannerSize::create([
            'width' => $validated['width'],
            'height' => $validated['height'],
        ]);

        return redirect()->route('banner-sizes-index')->with('success', 'Banner size added successfully!');
    }

    public function destroy($id)
    {
        BannerSize::findOrFail($id)->delete();
        return redirect()->route('banner-sizes-index');
    }
}
