<?php

declare(strict_types=1);

namespace Tests\Feature\Client;

use App\Models\ColorPalette;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;

uses(RefreshDatabase::class);

/** Images are moved into public/uploads/colorPalette, not a fake disk. */
beforeEach(function () {
    $this->user = User::factory()->admin()->create();
    $this->dir = public_path('uploads/colorPalette');
    $this->existing = File::exists($this->dir)
        ? array_map('basename', File::files($this->dir)) : [];
});

afterEach(function () {
    if (! File::exists($this->dir)) {
        return;
    }
    foreach (File::files($this->dir) as $file) {
        if (! in_array($file->getFilename(), $this->existing, true)) {
            File::delete($file->getPathname());
        }
    }
});

function paletteImages(): array
{
    return [
        'header_image' => UploadedFile::fake()->image('header.png'),
        'feedbackTab_inactive_image' => UploadedFile::fake()->image('inactive.png'),
        'feedbackTab_active_image' => UploadedFile::fake()->image('active.png'),
        'rightSideTab_feedback_description_image' => UploadedFile::fake()->image('desc.png'),
        'rightSideTab_color_palette_image' => UploadedFile::fake()->image('palette.png'),
    ];
}

function paletteColours(): array
{
    return [
        'name' => 'Launch Blue',
        'primary' => '#3B82F6', 'secondary' => '#6366F1', 'tertiary' => '#1D4ED8',
        'quaternary' => '#111111', 'quinary' => '#222222', 'senary' => '#333333',
        'septenary' => '#444444',
    ];
}

it('creates a palette when every image is supplied', function () {
    $this->actingAs($this->user)
        ->post(route('color-palettes-store'), paletteColours() + paletteImages())
        ->assertSessionHasNoErrors();

    $palette = ColorPalette::sole();

    expect($palette->name)->toBe('Launch Blue');
    foreach (array_keys(paletteImages()) as $field) {
        expect($palette->$field)->toStartWith('uploads/colorPalette/');
    }
});

it('rejects a palette with no images instead of failing on the insert', function () {
    // Every image column is NOT NULL. Validation used to allow them missing, so
    // the insert reached MySQL and came back a 500 — which bypasses the global
    // validation toast entirely.
    $this->actingAs($this->user)
        ->post(route('color-palettes-store'), paletteColours())
        ->assertSessionHasErrors(array_keys(paletteImages()));

    expect(ColorPalette::count())->toBe(0);
});

it('keeps the stored images when an update sends no new files', function () {
    $palette = ColorPalette::factory()->create();
    $before = $palette->only(array_keys(paletteImages()));

    $this->actingAs($this->user)
        ->put(route('color-palettes-update', $palette->id), paletteColours())
        ->assertSessionHasNoErrors();

    $palette->refresh();

    expect($palette->name)->toBe('Launch Blue');
    foreach ($before as $field => $value) {
        expect($palette->$field)->toBe($value);
    }
});

it('requires all seven colours', function () {
    $this->actingAs($this->user)
        ->post(route('color-palettes-store'), ['name' => 'Bare'] + paletteImages())
        ->assertSessionHasErrors(['primary', 'quinary', 'senary', 'septenary']);
});
