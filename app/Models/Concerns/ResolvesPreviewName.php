<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Model;

/**
 * Resolves the owning preview's name for the activity log, without ever
 * triggering a lazy load.
 *
 * Every preview-child model puts the preview name in its activity-log name
 * ("Banner of: Summer Sale"), and Spatie calls `getActivitylogOptions()` from
 * the `updated` model event — i.e. in the middle of a save. The accessors used
 * to walk the parent chain there:
 *
 *     $this->version?->feedbackset?->feedback?->category?->preview?->name
 *
 * `AppServiceProvider` disables lazy loading outside production, so on any save
 * where the model was genuinely dirty that walk threw
 * LazyLoadingViolationException and returned a 500. Rearranging banners was the
 * quickest way to hit it: `position` changes, the event fires, and
 * `bulkEdit()` had fetched each level with `find()`, so nothing up the chain
 * was loaded. Unchanged rows never reached it, because Eloquent skips the
 * `updated` event when no attribute is dirty — which is why saves that only
 * touched names appeared to work.
 *
 * Walking only loaded relations makes every save safe and costs no queries. To
 * keep the name in the log, the caller sets the chain in memory before saving
 * (see NewPreviewController::bulkEdit).
 */
trait ResolvesPreviewName
{
    /**
     * @param  list<string>  $relations  Path from this model up to the preview.
     */
    protected function previewNameVia(array $relations): ?string
    {
        $node = $this;

        foreach ($relations as $relation) {
            if (! $node instanceof Model || ! $node->relationLoaded($relation)) {
                return null;
            }

            $node = $node->getRelation($relation);
        }

        return $node?->name;
    }
}
