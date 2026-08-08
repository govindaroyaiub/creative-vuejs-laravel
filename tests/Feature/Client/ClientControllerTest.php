<?php

declare(strict_types=1);

namespace Tests\Feature\Client;

use App\Models\Client;
use App\Models\ColorPalette;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->admin()->create();
    $this->palette = ColorPalette::factory()->create();

    // Logos are moved into public/logos, not a fake disk, so note what was
    // there and remove only what this test adds.
    $this->logoDir = public_path('logos');
    $this->existingLogos = is_dir($this->logoDir)
        ? array_map('basename', glob($this->logoDir . '/*') ?: [])
        : [];
});

afterEach(function () {
    if (! is_dir($this->logoDir)) {
        return;
    }

    foreach (glob($this->logoDir . '/*') ?: [] as $path) {
        if (! in_array(basename($path), $this->existingLogos, true)) {
            @unlink($path);
        }
    }
});

it('rejects a client with no logo instead of failing on the insert', function () {
    // A client must always have a logo: the column is NOT NULL and the preview
    // header renders it. Validation used to allow null, so the request got all
    // the way to MySQL and came back as
    // "SQLSTATE[23000] ... Column 'logo' cannot be null" — a 500, which Inertia
    // shows as a raw error page rather than a message the user can act on.
    $this->actingAs($this->user)
        ->post(route('clients-store'), [
            'name' => 'Nike',
            'website' => 'https://nike.com',
            'color_palette_id' => $this->palette->id,
        ])
        ->assertSessionHasErrors(['logo' => 'The logo field is required.']);

    expect(Client::count())->toBe(0);
});

it('creates a client with a logo', function () {
    $this->actingAs($this->user)
        ->post(route('clients-store'), [
            'name' => 'Adidas',
            'website' => 'https://adidas.com',
            'color_palette_id' => $this->palette->id,
            'logo' => UploadedFile::fake()->image('adidas.png'),
        ])
        ->assertRedirect(route('clients'));

    $client = Client::sole();

    expect($client->logo)->toContain('adidas.png');
    expect(file_exists(public_path('logos/' . $client->logo)))->toBeTrue();
});

it('still requires a name, website and palette', function () {
    $this->actingAs($this->user)
        ->post(route('clients-store'), [])
        ->assertSessionHasErrors(['name', 'website', 'color_palette_id', 'logo']);

    expect(Client::count())->toBe(0);
});

it('refuses an update that clears the colour palette', function () {
    // The column is NOT NULL and store() requires a palette, so update() must
    // too — it used to validate `nullable` and then write null.
    $client = Client::factory()->create(['color_palette_id' => $this->palette->id]);

    $this->actingAs($this->user)
        ->post(route('clients-update', $client->id), [
            'name' => 'Renamed',
            'website' => 'https://example.com',
        ])
        ->assertSessionHasErrors('color_palette_id');

    expect($client->fresh()->color_palette_id)->toBe($this->palette->id);
});

it('keeps the existing logo when an update sends no new file', function () {
    $client = Client::factory()->create([
        'logo' => 'existing.png',
        'color_palette_id' => $this->palette->id,
    ]);

    $this->actingAs($this->user)
        ->post(route('clients-update', $client->id), [
            'name' => 'Renamed',
            'website' => 'https://example.com',
            'color_palette_id' => $this->palette->id,
        ])
        ->assertSessionHasNoErrors();

    expect($client->fresh()->logo)->toBe('existing.png');
    expect($client->fresh()->name)->toBe('Renamed');
});
