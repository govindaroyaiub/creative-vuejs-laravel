<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\ColorPalette;
use App\Models\newPreview;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

/**
 * DEMO ONLY: the review-mode / comments / compare prototype at
 * /previews/review/{slug}, shown to managers before any of it is built for real.
 *
 * It renders the Show2 viewer with the same props, plus `history`: every round
 * of every project as a nested tree, so the prototype can show a creative's
 * versions across rounds. Comments and approvals in the prototype live in the
 * browser only; this controller writes nothing.
 *
 * Staff only (inside the auth + permission group). Safe to delete together with
 * resources/js/pages/Previews/Review.vue and Previews/Review/.
 */
class PreviewReviewDemoController extends Controller
{
    public function __invoke(string $slug)
    {
        $preview = newPreview::with(['client.colorPalette', 'colorPalette', 'uploader'])
            ->where('slug', $slug)
            ->firstOrFail();

        $palette = ColorPalette::find($preview->color_palette_id);

        $allColors = ColorPalette::where('status', 1)
            ->select('id', 'name', 'primary', 'secondary', 'tertiary', 'quaternary', 'quinary', 'senary', 'septenary')
            ->get();

        $authUserClientName = Client::select(['id', 'name'])->find(Auth::user()->client_id)?->name ?? 'Unknown';

        return Inertia::render('Previews/Review', [
            'preview'            => $preview,
            'client'             => Client::select(['id', 'name', 'logo'])->find($preview->client_id),
            'headerLogo'         => Client::select(['id', 'name', 'logo'])->find($preview->header_logo_id),
            'palette'            => $palette?->only(['id', 'name', 'primary', 'secondary', 'tertiary', 'quaternary', 'quinary', 'senary', 'septenary']),
            'allColors'          => $allColors,
            'authUserClientName' => $authUserClientName,
            'previewId'          => $preview->id,
            'isAuthenticated'    => true,
            'initialPayload'     => app(newPreviewApiController::class)->previewPayload((int) $preview->id),
            'history'            => $this->history($preview),
        ]);
    }

    /**
     * { categoryId: [ { id, name, sets: [ { id, name, versions: [ { id, name, assets } ] } ] } ] }
     * Rounds oldest first. Read-only.
     */
    private function history(newPreview $preview): array
    {
        $preview->load([
            'categories.feedbacks.feedbackSets.versions.banners.size',
            'categories.feedbacks.feedbackSets.versions.gifs.size',
            'categories.feedbacks.feedbackSets.versions.videos.size',
            'categories.feedbacks.feedbackSets.versions.socials',
        ]);

        $relation = fn (string $type) => match ($type) {
            'banner' => 'banners',
            'gif'    => 'gifs',
            'video'  => 'videos',
            'social' => 'socials',
            default  => null,
        };

        $out = [];
        foreach ($preview->categories as $category) {
            $rel = $relation((string) $category->type);
            $out[$category->id] = $category->feedbacks->sortBy('id')->values()->map(fn ($round) => [
                'id'   => $round->id,
                'name' => $round->name,
                'sets' => $round->feedbackSets->sortBy('id')->values()->map(fn ($set) => [
                    'id'       => $set->id,
                    'name'     => $set->name,
                    'versions' => $set->versions->sortBy('id')->values()->map(fn ($version) => [
                        'id'     => $version->id,
                        'name'   => $version->name,
                        'assets' => $rel ? $version->{$rel}->values() : [],
                    ])->all(),
                ])->all(),
            ])->all();
        }

        return $out;
    }
}
