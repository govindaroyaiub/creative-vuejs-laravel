<?php

use App\Support\SafeName;

test('a name containing a path separator collapses to one segment', function () {
    // The bug: str_replace(' ', '_', 'Nike / Adidas Pitch') left the slash
    // in place, so the upload landed a directory deeper than the stored path.
    expect(SafeName::segment('Nike / Adidas Pitch', 'banner'))
        ->toBe('nike-adidas-pitch')
        ->not->toContain('/');
});

test('backslashes and colons are dropped too', function () {
    expect(SafeName::segment('Q4\\Launch: Phase 2', 'banner'))
        ->toBe('q4launch-phase-2');
});

test('spaces still become separators', function () {
    expect(SafeName::segment('Summer Sale', 'banner'))->toBe('summer-sale');
});

test('a name with nothing transliterable falls back', function () {
    expect(SafeName::segment('日本語', 'banner'))->toBe('banner');
    expect(SafeName::segment('', 'video'))->toBe('video');
    expect(SafeName::segment(null, 'video'))->toBe('video');
});

test('leading and trailing dots cannot survive', function () {
    expect(SafeName::segment('..', 'banner'))->toBe('banner');
    expect(SafeName::segment('.hidden.', 'banner'))->toBe('hidden');
});
