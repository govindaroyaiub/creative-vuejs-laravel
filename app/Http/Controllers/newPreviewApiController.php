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
use App\Models\FileTransfer;
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
    /**
     * Gate every preview-tree API behind the preview's own
     * `requires_login` flag — the same check the show/show2 pages
     * perform at render time. Without this, any unauthenticated
     * caller could pull the full preview content of a login-gated
     * preview by guessing its numeric id, completely bypassing the
     * UUID slug + auth gate on the page route.
     *
     * Returns the preview model on success; aborts 403 on miss.
     */
    private function assertPreviewVisible(newPreview $preview): newPreview
    {
        if ($preview->requires_login && !Auth::check()) {
            abort(403, 'This preview requires you to be logged in.');
        }
        return $preview;
    }

    public function changeTheme(Request $request, $preview_id, $color_id)
    {
        $preview = newPreview::find($preview_id);
        if (!$preview) {
            return response()->json(['error' => 'Preview not found'], 404);
        }
        $this->assertPreviewVisible($preview);

        // Validate the target palette is real before writing it back.
        if (!ColorPalette::whereKey($color_id)->exists()) {
            return response()->json(['error' => 'Color palette not found'], 404);
        }

        $preview->color_palette_id = (int) $color_id;
        $preview->save();
        return response()->json(['success' => true, 'message' => 'Theme changed successfully']);
    }

    function renderCategories($id)
    {
        $preview = newPreview::with([
            'categories.feedbacks.feedbackSets.versions'
        ])->findOrFail($id);
        $this->assertPreviewVisible($preview);

        // Flatten categories, feedbacks, etc.
        $categories = $preview->categories;
        $activeCategory = $categories->where('is_active', 1)->first();
        $feedbacks = $activeCategory->feedbacks;
        $activeFeedback = $feedbacks->where('is_active', 1)->first();
        $feedbackSets = $activeFeedback->feedbackSets;
        $versions = $feedbackSets->flatMap->versions;
        $fileTransfer = FileTransfer::find($activeCategory->file_transfer_id);
        if (!$fileTransfer) {
            $fileTransfer = null;
        }

        return response()->json([
            'preview' => $preview,
            'categories' => $categories,
            'feedbacks' => $feedbacks,
            'feedbackSets' => $feedbackSets,
            'activeCategory' => $activeCategory,
            'activeFeedback' => $activeFeedback,
            'versions' => $versions,
            'fileTransfer' => $fileTransfer
        ]);
    }

    function updateActiveCategory($id)
    {
        $category = newCategory::findOrFail($id);
        $preview = newPreview::findOrFail($category->preview_id);
        $this->assertPreviewVisible($preview);

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
        $fileTransfer = FileTransfer::find($activeCategory->file_transfer_id);
        if (!$fileTransfer) {
            $fileTransfer = null;
        }

        return response()->json([
            'preview' => $preview,
            'categories' => $categories,
            'feedbacks' => $feedbacks,
            'feedbackSets' => $feedbackSets,
            'activeCategory' => $activeCategory,
            'activeFeedback' => $activeFeedback,
            'versions' => $versions,
            'fileTransfer' => $fileTransfer
        ]);
    }

    function updateActiveFeedback($id)
    {
        $feedback = newFeedback::findOrFail($id);
        $preview = newPreview::findOrFail($feedback->category->preview_id);
        $this->assertPreviewVisible($preview);

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
        $fileTransfer = FileTransfer::find($activeCategory->file_transfer_id);
        if (!$fileTransfer) {
            $fileTransfer = null;
        }

        return response()->json([
            'preview' => $preview,
            'categories' => $categories,
            'feedbacks' => $feedbacks,
            'feedbackSets' => $feedbackSets,
            'activeCategory' => $activeCategory,
            'activeFeedback' => $activeFeedback,
            'versions' => $versions,
            'fileTransfer' => $fileTransfer
        ]);
    }

    /**
     * Walk version_id (or feedback_set_id) up to its preview and gate
     * by `requires_login`. The render endpoints feed Show2's lazy-load
     * pattern (versions → assets), so each one needs the same gate as
     * the page route they support.
     */
    private function assertVersionVisible(int $versionId): void
    {
        $version = newVersion::find($versionId);
        if (!$version) {
            abort(404);
        }
        $preview = newPreview::query()
            ->whereHas('categories.feedbacks.feedbackSets.versions', fn($q) => $q->whereKey($versionId))
            ->first();
        if (!$preview) {
            abort(404);
        }
        $this->assertPreviewVisible($preview);
    }

    private function assertFeedbackSetVisible(int $feedbackSetId): void
    {
        $set = newFeedbackSet::find($feedbackSetId);
        if (!$set) {
            abort(404);
        }
        $preview = newPreview::query()
            ->whereHas('categories.feedbacks.feedbackSets', fn($q) => $q->whereKey($feedbackSetId))
            ->first();
        if (!$preview) {
            abort(404);
        }
        $this->assertPreviewVisible($preview);
    }

    function renderVersions($feedbackSet_id)
    {
        $this->assertFeedbackSetVisible((int) $feedbackSet_id);
        $versions = newVersion::where('feedback_set_id', $feedbackSet_id)->get();
        return response()->json([
            'versions' => $versions
        ]);
    }

    function renderBanners($version_id)
    {
        $this->assertVersionVisible((int) $version_id);
        $banners = newBanner::with('size')->where('version_id', $version_id)->orderBy('position')->get();
        return response()->json([
            'banners' => $banners
        ]);
    }

    function renderGifs($version_id)
    {
        $this->assertVersionVisible((int) $version_id);
        $gifs = newGif::with('size')->where('version_id', $version_id)->orderBy('position')->get();
        return response()->json([
            'gifs' => $gifs
        ]);
    }

    function renderSocials($version_id)
    {
        $this->assertVersionVisible((int) $version_id);
        $socials = newSocial::where('version_id', $version_id)->orderBy('position')->get();
        return response()->json([
            'socials' => $socials
        ]);
    }

    function renderVideos($version_id)
    {
        $this->assertVersionVisible((int) $version_id);
        $videos = newVideo::with('size')->where('version_id', $version_id)->orderBy('position')->get();
        return response()->json([
            'videos' => $videos
        ]);
    }
}
