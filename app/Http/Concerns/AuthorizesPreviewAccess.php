<?php

namespace App\Http\Concerns;

use App\Models\Client;
use App\Models\newBanner;
use App\Models\newCategory;
use App\Models\newFeedback;
use App\Models\newFeedbackSet;
use App\Models\newGif;
use App\Models\newPreview;
use App\Models\newSocial;
use App\Models\newVersion;
use App\Models\newVideo;
use Illuminate\Support\Facades\Auth;

/**
 * Centralised access checks for preview-scoped controllers.
 *
 * Every endpoint that mutates or reads a single preview-child
 * resource (banner, video, social, gif, version, set, feedback,
 * category) must call one of these helpers before doing any work,
 * otherwise an authenticated user from one client can act on
 * another client's resources by id-enumeration (IDOR).
 *
 * Ownership rule, mirroring NewPreviewController::index():
 *   1. Planet Nine staff (auth user's client.name === 'Planet Nine')
 *      can act on any preview.
 *   2. Otherwise, the auth user's `client_id` must equal the
 *      preview's `client_id`, OR the user's id must appear in the
 *      preview's `team_members` JSON array.
 */
trait AuthorizesPreviewAccess
{
    /**
     * Cached "is this user Planet Nine staff?" lookup. The client
     * name lookup is one DB hit per request.
     */
    private ?bool $isPlanetNineStaffCache = null;

    private function isPlanetNineStaff(): bool
    {
        if ($this->isPlanetNineStaffCache !== null) {
            return $this->isPlanetNineStaffCache;
        }
        $user = Auth::user();
        if (!$user) {
            return $this->isPlanetNineStaffCache = false;
        }
        $client = Client::find($user->client_id);
        return $this->isPlanetNineStaffCache = ($client?->name === 'Planet Nine');
    }

    /**
     * Assert the current request is allowed to act on $preview.
     * Aborts 403 on miss.
     */
    protected function authorizePreview(newPreview $preview): newPreview
    {
        $user = Auth::user();
        if (!$user) {
            abort(401);
        }

        if ($this->isPlanetNineStaff()) {
            return $preview;
        }

        if ((int) $user->client_id === (int) $preview->client_id) {
            return $preview;
        }

        $team = $preview->team_members;
        if (is_array($team) && in_array($user->id, $team, false)) {
            return $preview;
        }

        abort(403, 'You are not allowed to access this preview.');
    }

    /**
     * Look up the preview for an arbitrary preview-child id (or
     * model) and run the ownership check. Returns the resolved
     * preview so callers can reuse it.
     */
    protected function authorizePreviewById(int $previewId): newPreview
    {
        $preview = newPreview::findOrFail($previewId);
        return $this->authorizePreview($preview);
    }

    protected function authorizeBanner(newBanner $banner): newPreview
    {
        $preview = $banner->version?->feedbackset?->feedback?->category?->preview;
        if (!$preview) {
            abort(404);
        }
        return $this->authorizePreview($preview);
    }

    protected function authorizeVideo(newVideo $video): newPreview
    {
        $preview = $video->version?->feedbackSet?->feedback?->category?->preview;
        if (!$preview) {
            abort(404);
        }
        return $this->authorizePreview($preview);
    }

    protected function authorizeSocial(newSocial $social): newPreview
    {
        $preview = $social->version?->feedbackSet?->feedback?->category?->preview;
        if (!$preview) {
            abort(404);
        }
        return $this->authorizePreview($preview);
    }

    protected function authorizeGif(newGif $gif): newPreview
    {
        $preview = $gif->version?->feedbackSet?->feedback?->category?->preview;
        if (!$preview) {
            abort(404);
        }
        return $this->authorizePreview($preview);
    }

    protected function authorizeVersion(newVersion $version): newPreview
    {
        $preview = $version->feedbackSet?->feedback?->category?->preview
            ?? $version->feedbackset?->feedback?->category?->preview;
        if (!$preview) {
            abort(404);
        }
        return $this->authorizePreview($preview);
    }

    protected function authorizeFeedbackSet(newFeedbackSet $set): newPreview
    {
        $preview = $set->feedback?->category?->preview;
        if (!$preview) {
            abort(404);
        }
        return $this->authorizePreview($preview);
    }

    protected function authorizeFeedback(newFeedback $feedback): newPreview
    {
        $preview = $feedback->category?->preview;
        if (!$preview) {
            abort(404);
        }
        return $this->authorizePreview($preview);
    }

    protected function authorizeCategory(newCategory $category): newPreview
    {
        $preview = $category->preview;
        if (!$preview) {
            abort(404);
        }
        return $this->authorizePreview($preview);
    }
}
