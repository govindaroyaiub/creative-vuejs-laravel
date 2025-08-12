<?php

namespace App\Http\Controllers;

use App\Models\newFeedback;
use Illuminate\Http\Request;

class NewFeedbackController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $feedbacks = $category->feedbacks()
            ->select('id','name','is_active')
            ->withCount('feedbackSets')
            ->orderBy('id','desc')
            ->get();

        return response()->json($feedbacks);
    }

    public function toggleActive(newFeedback $feedback)
    {
        $feedback->is_active = ! $feedback->is_active;
        $feedback->save();
        return response()->json(['is_active' => $feedback->is_active]);
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
    public function show(newFeedback $newFeedback)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(newFeedback $newFeedback)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, newFeedback $newFeedback)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(newFeedback $newFeedback)
    {
        //
    }
}
