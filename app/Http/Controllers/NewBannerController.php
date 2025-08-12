<?php

namespace App\Http\Controllers;

use App\Models\newBanner;
use Illuminate\Http\Request;

class NewBannerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $banners = $version->banners()
            ->select('id','name','path','size_id','file_size','position')
            ->orderBy('position')
            ->paginate(20);
        return response()->json($banners);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(newBanner $newBanner)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(newBanner $newBanner)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, newBanner $newBanner)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(newBanner $newBanner)
    {
        //
    }
}
