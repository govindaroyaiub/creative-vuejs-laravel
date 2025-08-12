<?php

namespace App\Http\Controllers;

use App\Models\newVersion;
use Illuminate\Http\Request;

class NewVersionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return response()->json(
            $set->versions()->select('id','name')->orderBy('id','desc')->get()
        );
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
    public function show(newVersion $newVersion)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(newVersion $newVersion)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, newVersion $newVersion)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(newVersion $newVersion)
    {
        //
    }
}
