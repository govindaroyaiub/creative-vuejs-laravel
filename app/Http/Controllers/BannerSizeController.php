<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use App\Models\BannerSize;
use Illuminate\Validation\Rule;

class BannerSizeController extends Controller
{
    public function index(Request $request)
    {
        $query = BannerSize::query();

        if ($search = $request->input('search')) {
            $query->where('width', 'like', "%{$search}%")
                ->orWhere('height', 'like', "%{$search}%");
        }

        $bannerSizes = $query
            ->orderBy('width', 'asc')
            ->orderBy('height', 'asc')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('BannerSizes/Index', [
            'bannerSizes' => $bannerSizes,
            'search' => $search, // optional: pass it back for binding
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
            'flash' => session('success'),
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
            return Redirect::route('banner-sizes-index')->with('error', 'Sorry! This banner size already exists!');
        }

        BannerSize::create($validated);

        return redirect()->route('banner-sizes-index')
            ->with('success', 'Banner size added successfully!');
    }

    public function update(Request $request, $id)
    {
        $bannerSize = BannerSize::findOrFail($id);

        $request->validate([
            'width' => [
                'required',
                'numeric',
                Rule::unique('banner_sizes')
                    ->where(function ($query) use ($request) {
                        return $query->where('height', $request->height);
                    })
                    ->ignore($bannerSize->id),
            ],
            'height' => 'required|numeric',
        ], [
            'width.unique' => 'Sorry, width and height already exist.',
        ]);

        $bannerSize->update([
            'width' => $request->width,
            'height' => $request->height,
        ]);

        return redirect()->route('banner-sizes', $id)
            ->with('success', 'Banner size updated successfully.');
    }

    public function destroy($id)
    {
        BannerSize::findOrFail($id)->delete();
        return redirect()->route('banner-sizes-index');
    }
}
