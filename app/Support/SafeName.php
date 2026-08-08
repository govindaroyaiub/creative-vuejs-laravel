<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Turns a user-supplied name (preview titles, mostly) into one
 * filesystem path segment.
 *
 * Banner and video uploads used to build their directory with
 * `str_replace(' ', '_', $preview->name)`, which only handles spaces.
 * A preview called "Nike / Adidas Pitch" produced
 * `uploads/banners/Nike_/_Adidas_Pitch_68f3.../` — the upload landed
 * one directory deeper than the `path` column claimed, so the preview
 * iframe 404'd and `deletePath()` never cleaned the stray directory up.
 *
 * `Str::slug()` is already the convention for gif/social/video
 * filenames, so it is used here too: it drops every path separator
 * rather than trying to enumerate the unsafe ones.
 */
class SafeName
{
    /**
     * @param  string  $fallback  Used when $name slugs down to nothing
     *                            (empty, or entirely non-transliterable).
     */
    public static function segment(?string $name, string $fallback): string
    {
        $slug = Str::slug((string) $name);

        return $slug !== '' ? $slug : $fallback;
    }
}
