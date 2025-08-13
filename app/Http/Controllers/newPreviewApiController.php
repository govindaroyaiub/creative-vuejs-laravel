<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\newPreview;
use App\Models\newCategory;
use App\Models\newFeedback;
use App\Models\newFeedbackSet;
use App\Models\newVersion;
use App\Models\newBanner;
use Illuminate\Support\Facades\DB;

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

    public function getVersionsAndBanners($feedbackSetId)
    {
        // Get all versions for this feedback set
        $versions = newVersion::where('feedback_set_id', $feedbackSetId)->get();

        // Get all banners for these versions, join with banner_sizes for dimensions
        $versionIds = $versions->pluck('id');
        $banners = DB::table('new_banners')
            ->join('banner_sizes', 'new_banners.size_id', '=', 'banner_sizes.id')
            ->whereIn('new_banners.version_id', $versionIds)
            ->orderBy('new_banners.position', 'asc')
            ->select(
                'new_banners.id',
                'new_banners.name',
                'new_banners.path',
                'new_banners.file_size',
                'new_banners.position',
                'new_banners.version_id', // <-- Add this line!
                'banner_sizes.width',
                'banner_sizes.height'
            )
            ->get();

        // Group banners under their version
        $versionsWithBanners = $versions->map(function ($version) use ($banners) {
            $versionBanners = $banners->where('version_id', $version->id)->values();
            // Filter banners for this version
            $filteredBanners = $banners->filter(function ($banner) use ($version) {
                return $banner->version_id == $version->id;
            })->values()->map(function ($banner) {
                return [
                    'id' => $banner->id,
                    'name' => $banner->name,
                    'path' => $banner->path,
                    'file_size' => $banner->file_size,
                    'position' => $banner->position,
                    'width' => $banner->width,
                    'height' => $banner->height,
                ];
            });
            return [
                'id' => $version->id,
                'name' => $version->name,
                'banners' => $filteredBanners
            ];
        });

        return response()->json([
            'versions' => $versionsWithBanners
        ]);
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
