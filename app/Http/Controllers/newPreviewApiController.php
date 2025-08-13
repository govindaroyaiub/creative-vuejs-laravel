<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\newPreview;
use App\Models\newCategory;
use App\Models\newFeedback;
use App\Models\newFeedbackSet;
use App\Models\newVersion;
use App\Models\newBanner;

class newPreviewApiController extends Controller
{
    public function getAllCategories($id)
    {
        $categories = newCategory::where('preview_id', $id)->get();
        $activeCategory = newCategory::where('preview_id', $id)->where('is_active', 1)->first();
        return $data = [
            'categories' => $categories,
            'activeCategory_id' => $activeCategory['id']
        ];
    }

    public function fetchCategoryType($id)
    {
        $category = newCategory::find($id);
        $feedbacks = newFeedback::where('category_id', $id)->get();
        $activeFeedback = newFeedback::where('category_id', $id)->where('is_active', 1)->first();

        return $data = [
            'type' => $category['type'],
            'feedbacks' => $feedbacks,
            'category_name' => $category['name'],
            'activeFeedback_id' => $activeFeedback['id'],
            'category_id' => $id
        ];
    }

    public function fetchFeedbackSets($id)
    {
        $feedbackSets = newFeedbackSet::where('feedback_id', $id)->get();

        return $data = [
            'feedbackSets' => $feedbackSets
        ];
    }

    public function changeTheme($preview_id, $color_id)
    {
        $preview = newPreview::find($preview_id);

        if (!$preview) {
            return response()->json(['error' => 'Preview not found'], 404);
        }

        $preview->color_palette_id = $color_id;
        $preview->save();
        return response()->json(['success' => true, 'message' => 'Theme changed successfully']);
    }
}
