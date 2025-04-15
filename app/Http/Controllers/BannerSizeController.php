<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use App\Models\BannerSize;

class BannerSizeController extends Controller
{
    public function create(Request $request){
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
}
