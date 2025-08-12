<?php

namespace App\Http\Controllers;

use App\Models\newCategory;
use App\Models\newPreview;
use Illuminate\Http\Request;

class NewCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(newPreview $preview)
    {
        dd($preview);
        $cats = $preview->categories()
            ->select('id','name','is_active')
            ->where('type','banner')
            ->withCount('feedbacks')
            ->orderBy('id','desc')
            ->get();

        return response()->json($cats);
    }

    public function toggleActive(newCategory $category)
    {
        $category->is_active = ! $category->is_active;
        $category->save();
        return response()->json(['is_active' => $category->is_active]);
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
    public function show(newCategory $newCategory)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(newCategory $newCategory)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, newCategory $newCategory)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(newCategory $newCategory)
    {
        //
    }
}
