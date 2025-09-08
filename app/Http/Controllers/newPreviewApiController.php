<?php

namespace App\Http\Controllers;

use App\Models\newPreview;
use Illuminate\Http\Request;
use ZipArchive;
use Inertia\Inertia;
use getID3;
use App\Models\Client;
use App\Models\User;
use App\Models\BannerSize;
use App\Models\newCategory;
use App\Models\newFeedback;
use App\Models\newFeedbackSet;
use App\Models\newVersion;
use App\Models\newBanner;
use App\Models\ColorPalette;
use App\Models\VideoSize;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use App\Models\newVideo;
use App\Models\newGif;
use App\Models\newSocial;
use Illuminate\Validation\Rule;

class newPreviewApiController extends Controller
{
    function renderCategories($id)
    {
        $preview = newPreview::with([
            'categories.feedbacks.feedbackSets.versions'
        ])->findOrFail($id);

        // Flatten categories, feedbacks, etc.
        $categories = $preview->categories;
        $activeCategory = $categories->where('is_active', 1)->first();
        $feedbacks = $activeCategory->feedbacks;
        $activeFeedback = $feedbacks->where('is_active', 1)->first();
        $feedbackSets = $activeFeedback->feedbackSets;
        $versions = $feedbackSets->flatMap->versions;

        return response()->json([
            'preview' => $preview,
            'categories' => $categories,
            'feedbacks' => $feedbacks,
            'feedbackSets' => $feedbackSets,
            'activeCategory' => $activeCategory,
            'versions' => $versions
        ]);
    }

    function renderVersions($feedbackSet_id)
    {
        $versions = newVersion::where('feedback_set_id', $feedbackSet_id)->get();
        return response()->json([
            'versions' => $versions
        ]);
    }

    function renderBanners($version_id)
    {
        $banners = newBanner::with('size')->where('version_id', $version_id)->get();
        return response()->json([
            'banners' => $banners
        ]);
    }

    function updateActiveCategory($id)
    {
        $category = newCategory::findOrFail($id);

        // Set all categories under the same preview to inactive
        newCategory::where('preview_id', $category->preview_id)
            ->update(['is_active' => 0]);

        // Set the selected category to active
        $category->is_active = 1;
        $category->save();

        $preview = newPreview::with([
            'categories.feedbacks.feedbackSets.versions'
        ])->findOrFail($category->preview_id);

        // Flatten categories, feedbacks, etc.
        $categories = $preview->categories;
        $activeCategory = $categories->where('is_active', 1)->first();
        $feedbacks = $activeCategory->feedbacks;
        $activeFeedback = $feedbacks->where('is_active', 1)->first();
        $feedbackSets = $activeFeedback->feedbackSets;
        $versions = $feedbackSets->flatMap->versions;

        return response()->json([
            'preview' => $preview,
            'categories' => $categories,
            'feedbacks' => $feedbacks,
            'feedbackSets' => $feedbackSets,
            'activeCategory' => $activeCategory,
            'versions' => $versions
        ]);
    }

    function updateActiveFeedback($id){
        $feedback = newFeedback::findOrFail($id);

        // Set all feedbacks under the same category to inactive
        newFeedback::where('category_id', $feedback->category_id)
            ->update(['is_active' => 0]);

        // Set the selected feedback to active
        $feedback->is_active = 1;
        $feedback->save();

        $preview = newPreview::with([
            'categories.feedbacks.feedbackSets.versions'
        ])->findOrFail($feedback->category->preview_id);

        // Flatten categories, feedbacks, etc.
        $categories = $preview->categories;
        $activeCategory = $categories->where('is_active', 1)->first();
        $feedbacks = $activeCategory->feedbacks;
        $activeFeedback = $feedbacks->where('is_active', 1)->first();
        $feedbackSets = $activeFeedback->feedbackSets;
        $versions = $feedbackSets->flatMap->versions;

        return response()->json([
            'preview' => $preview,
            'categories' => $categories,
            'feedbacks' => $feedbacks,
            'feedbackSets' => $feedbackSets,
            'activeCategory' => $activeCategory,
            'versions' => $versions
        ]);
    }

    function renderGifs($version_id){
        $gifs = newGif::with('size')->where('version_id', $version_id)->get();
        return response()->json([
            'gifs' => $gifs
        ]);
    }

    function renderSocials($version_id){
        $socials = newSocial::where('version_id', $version_id)->get();
        return response()->json([
            'socials' => $socials
        ]);
    }

    function renderVideos($version_id){
        $videos = newVideo::with('size')->where('version_id', $version_id)->get();
        return response()->json([
            'videos' => $videos
        ]);
    }
}
