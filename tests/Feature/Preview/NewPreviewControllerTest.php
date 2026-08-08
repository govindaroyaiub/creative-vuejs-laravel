<?php

declare(strict_types=1);

namespace Tests\Feature\Preview;

use App\Models\Client;
use App\Models\ColorPalette;
use App\Models\User;
use App\Models\newCategory;
use App\Models\newPreview;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Rewritten against the real controller. The previous version posted to
 * `new-preview.store` / `new-preview.index` (the real names are
 * `previews-store` / `previews-index`) and assumed previews had
 * `title`/`description`/`file_path` columns and soft deletes.
 *
 * Access rules mirror AuthorizesPreviewAccess: Planet Nine staff see
 * everything, everyone else is scoped to their own client, plus any preview
 * whose `team_members` lists them.
 */
function planetNine(): User
{
    $client = Client::factory()->create(['name' => 'Planet Nine']);

    return User::factory()->admin()->create(['client_id' => $client->id]);
}

function clientStaff(Client $client): User
{
    return User::factory()->admin()->create(['client_id' => $client->id]);
}

/**
 * A preview whose searchable relations carry fixed, inert names.
 *
 * The index query matches the search term against the preview name, its
 * client's name AND its uploader's name. Leaving those to the factory means
 * faker can hand a decoy row a company like "Summers Ltd" or a person called
 * "Adam", which matches a search for "Summer" or "Ada" and makes an exact-count
 * assertion fail at random.
 */
function decoyPreview(string $name): newPreview
{
    return newPreview::factory()
        ->forClient(Client::factory()->create(['name' => 'Qqq Decoy Client']))
        ->uploadedBy(User::factory()->create(['name' => 'Qqq Decoy Uploader']))
        ->create(['name' => $name]);
}

describe('access control', function () {
    it('redirects guests to login', function () {
        $this->get(route('previews-index'))->assertRedirect(route('login'));
        $this->post(route('previews-store'))->assertRedirect(route('login'));
    });

    it('refuses a signed-in user with no permissions', function () {
        $this->actingAs(User::factory()->create())
            ->get(route('previews-index'))
            ->assertStatus(403);
    });

    it('lets Planet Nine staff delete any preview', function () {
        $preview = newPreview::factory()->create();

        $this->actingAs(planetNine())
            ->delete(route('previews-delete', $preview->id))
            ->assertRedirect(route('previews-index'));

        expect(newPreview::find($preview->id))->toBeNull();
    });

    it('stops one client deleting another client\'s preview', function () {
        $preview = newPreview::factory()->create();
        $outsider = clientStaff(Client::factory()->create());

        $this->actingAs($outsider)
            ->delete(route('previews-delete', $preview->id))
            ->assertStatus(403);

        expect(newPreview::find($preview->id))->not->toBeNull();
    });

    it('lets a client act on its own preview', function () {
        $client = Client::factory()->create();
        $preview = newPreview::factory()->forClient($client)->create();

        $this->actingAs(clientStaff($client))
            ->delete(route('previews-delete', $preview->id))
            ->assertRedirect(route('previews-index'));

        expect(newPreview::find($preview->id))->toBeNull();
    });

    it('lets a listed team member act on a preview outside their client', function () {
        $member = clientStaff(Client::factory()->create());
        $preview = newPreview::factory()->withTeam([$member->id])->create();

        $this->actingAs($member)
            ->delete(route('previews-delete', $preview->id))
            ->assertRedirect(route('previews-index'));

        expect(newPreview::find($preview->id))->toBeNull();
    });
});

describe('listing', function () {
    it('shows Planet Nine staff every preview, newest first', function () {
        newPreview::factory()->create(['name' => 'Older', 'created_at' => now()->subDay()]);
        newPreview::factory()->create(['name' => 'Newer', 'created_at' => now()]);

        $this->actingAs(planetNine())
            ->get(route('previews-index'))
            ->assertStatus(200)
            ->assertInertia(fn ($page) => $page->component('Previews/Index')
                ->where('previews.data.0.name', 'Newer')
                ->where('previews.data.1.name', 'Older'));
    });

    it('scopes a client to its own previews', function () {
        $client = Client::factory()->create();
        newPreview::factory()->forClient($client)->create(['name' => 'Mine']);
        newPreview::factory()->create(['name' => 'Theirs']);

        $this->actingAs(clientStaff($client))
            ->get(route('previews-index'))
            ->assertStatus(200)
            ->assertInertia(fn ($page) => $page->count('previews.data', 1)
                ->where('previews.data.0.name', 'Mine'));
    });

    it('does not crash for a user with no client at all', function () {
        // client_id is nullable and deleting a client nulls it.
        newPreview::factory()->create();

        $this->actingAs(User::factory()->admin()->create(['client_id' => null]))
            ->get(route('previews-index'))
            ->assertStatus(200);
    });

    it('searches by preview name', function () {
        decoyPreview('Summer Sale');
        decoyPreview('Winter Sale');

        $this->actingAs(planetNine())
            ->get(route('previews-index', ['search' => 'Summer']))
            ->assertInertia(fn ($page) => $page->count('previews.data', 1)
                ->where('previews.data.0.name', 'Summer Sale'));
    });

    it('searches by client name', function () {
        $nike = Client::factory()->create(['name' => 'Nike']);
        newPreview::factory()->forClient($nike)
            ->uploadedBy(User::factory()->create(['name' => 'Qqq Decoy Uploader']))
            ->create(['name' => 'Boots']);
        decoyPreview('Something Else');

        $this->actingAs(planetNine())
            ->get(route('previews-index', ['search' => 'Nike']))
            ->assertInertia(fn ($page) => $page->count('previews.data', 1)
                ->where('previews.data.0.name', 'Boots'));
    });

    it('searches by uploader name', function () {
        $uploader = User::factory()->create(['name' => 'Ada Lovelace']);
        newPreview::factory()->uploadedBy($uploader)
            ->forClient(Client::factory()->create(['name' => 'Qqq Decoy Client']))
            ->create(['name' => 'Hers']);
        decoyPreview('Someone Else\'s');

        $this->actingAs(planetNine())
            ->get(route('previews-index', ['search' => 'Ada']))
            ->assertInertia(fn ($page) => $page->count('previews.data', 1)
                ->where('previews.data.0.name', 'Hers'));
    });

    it('searches by a written-out date', function () {
        // Exercises the driver-aware date expression in SqlDate.
        newPreview::factory()->create(['created_at' => '2026-08-07 12:00:00']);
        newPreview::factory()->create(['created_at' => '2024-03-03 12:00:00']);

        $this->actingAs(planetNine())
            ->get(route('previews-index', ['search' => 'August 2026']))
            ->assertInertia(fn ($page) => $page->count('previews.data', 1));
    });

    it('filters by a date range', function () {
        newPreview::factory()->create(['name' => 'In range', 'created_at' => '2026-06-15 12:00:00']);
        newPreview::factory()->create(['name' => 'Too early', 'created_at' => '2026-01-01 12:00:00']);

        $this->actingAs(planetNine())
            ->get(route('previews-index', ['from_date' => '2026-06-01', 'to_date' => '2026-06-30']))
            ->assertInertia(fn ($page) => $page->count('previews.data', 1)
                ->where('previews.data.0.name', 'In range'));
    });

    it('filters by uploader', function () {
        $uploader = User::factory()->create();
        newPreview::factory()->uploadedBy($uploader)->create(['name' => 'Theirs']);
        newPreview::factory()->create(['name' => 'Not theirs']);

        $this->actingAs(planetNine())
            ->get(route('previews-index', ['uploader_id' => $uploader->id]))
            ->assertInertia(fn ($page) => $page->count('previews.data', 1)
                ->where('previews.data.0.name', 'Theirs'));
    });

    it('resolves team member ids into names for the page', function () {
        $member = User::factory()->create(['name' => 'Grace Hopper']);
        newPreview::factory()->withTeam([$member->id])->create();

        $this->actingAs(planetNine())
            ->get(route('previews-index'))
            ->assertInertia(fn ($page) => $page->where('previews.data.0.team_users.0.name', 'Grace Hopper'));
    });

    it('paginates ten per page in table view', function () {
        newPreview::factory(12)->create();

        $this->actingAs(planetNine())
            ->get(route('previews-index', ['view' => 'table']))
            ->assertInertia(fn ($page) => $page->count('previews.data', 10)
                ->where('previews.total', 12));
    });
});

describe('creating', function () {
    it('creates a preview owned by the signed-in uploader', function () {
        $user = planetNine();
        $client = Client::factory()->create();
        $palette = ColorPalette::factory()->create();
        $member = User::factory()->create();

        $this->actingAs($user)
            ->post(route('previews-store'), [
                'name' => 'Summer Sale 2026',
                'client_id' => $client->id,
                'header_logo_id' => $client->id,
                'color_palette_id' => $palette->id,
                'requires_login' => false,
                'show_planetnine_logo' => true,
                'show_sidebar_logo' => true,
                'show_footer' => true,
                'team_ids' => [$member->id],
            ])
            ->assertSessionHas('success');

        $preview = newPreview::sole();

        expect($preview->name)->toBe('Summer Sale 2026');
        expect($preview->client_id)->toBe($client->id);
        expect($preview->uploader_id)->toBe($user->id);
        expect($preview->team_members)->toBe([$member->id]);
        expect($preview->slug)->toMatch('/^[0-9a-f]{8}-/');
    });

    it('redirects to the asset editor for the new preview', function () {
        $client = Client::factory()->create();
        $palette = ColorPalette::factory()->create();

        $this->actingAs(planetNine())
            ->post(route('previews-store'), [
                'name' => 'Fresh',
                'client_id' => $client->id,
                'header_logo_id' => $client->id,
                'color_palette_id' => $palette->id,
                'team_ids' => [User::factory()->create()->id],
            ])
            ->assertRedirect(route('previews.update.all', newPreview::sole()->id));
    });

    it('defaults the display toggles when they are not sent', function () {
        // The toggles are optional in the validator but their columns are NOT
        // NULL, so omitting them used to 500 on insert.
        $client = Client::factory()->create();

        $this->actingAs(planetNine())
            ->post(route('previews-store'), [
                'name' => 'No toggles',
                'client_id' => $client->id,
                'header_logo_id' => $client->id,
                'color_palette_id' => ColorPalette::factory()->create()->id,
                'team_ids' => [User::factory()->create()->id],
            ])
            ->assertSessionHas('success');

        $preview = newPreview::sole();

        expect($preview->requires_login)->toBeFalsy();
        expect($preview->show_planetnine_logo)->toBeTruthy();
        expect($preview->show_sidebar_logo)->toBeTruthy();
        expect($preview->show_footer)->toBeTruthy();
    });

    it('persists show_footer when it is switched off', function () {
        $client = Client::factory()->create();

        $this->actingAs(planetNine())
            ->post(route('previews-store'), [
                'name' => 'Footerless',
                'client_id' => $client->id,
                'header_logo_id' => $client->id,
                'color_palette_id' => ColorPalette::factory()->create()->id,
                'show_footer' => false,
                'team_ids' => [User::factory()->create()->id],
            ]);

        expect(newPreview::sole()->show_footer)->toBeFalsy();
    });

    it('requires a name, client, header logo, palette and team', function () {
        $this->actingAs(planetNine())
            ->post(route('previews-store'), [])
            ->assertSessionHasErrors([
                'name', 'client_id', 'header_logo_id', 'color_palette_id', 'team_ids',
            ]);

        expect(newPreview::count())->toBe(0);
    });

    it('rejects ids that do not exist', function () {
        $this->actingAs(planetNine())
            ->post(route('previews-store'), [
                'name' => 'Bad refs',
                'client_id' => 99999,
                'header_logo_id' => 99999,
                'color_palette_id' => 99999,
                'team_ids' => [99999],
            ])
            ->assertSessionHasErrors([
                'client_id', 'header_logo_id', 'color_palette_id', 'team_ids.0',
            ]);

        expect(newPreview::count())->toBe(0);
    });
});

describe('public viewing', function () {
    it('shows a preview by slug', function () {
        $preview = newPreview::factory()->create();

        $this->get(route('previews-show', $preview->slug))
            ->assertStatus(200)
            ->assertInertia(fn ($page) => $page->component('Previews/Show'));
    });

    it('asks for a login when the preview requires one', function () {
        $preview = newPreview::factory()->requiringLogin()->create();

        $this->get(route('previews-show', $preview->slug))
            ->assertStatus(200)
            ->assertInertia(fn ($page) => $page->component('Previews/Login')
                ->where('preview_id', $preview->id));
    });

    it('lets a signed-in visitor past the login gate', function () {
        $preview = newPreview::factory()->requiringLogin()->create();

        $this->actingAs(planetNine())
            ->get(route('previews-show', $preview->slug))
            ->assertInertia(fn ($page) => $page->component('Previews/Show'));
    });

    it('404s an unknown slug', function () {
        $this->get(route('previews-show', 'not-a-real-slug'))->assertStatus(404);
    });
});

describe('deleting', function () {
    it('cascades categories away with the preview', function () {
        $preview = newPreview::factory()->create();
        $category = newCategory::create([
            'preview_id' => $preview->id, 'name' => 'Banners', 'type' => 'banner',
        ]);

        $this->actingAs(planetNine())
            ->delete(route('previews-delete', $preview->id))
            ->assertRedirect(route('previews-index'))
            ->assertSessionHas('success');

        expect(newPreview::find($preview->id))->toBeNull();
        expect(newCategory::find($category->id))->toBeNull();
    });

    it('404s deleting a preview that is already gone', function () {
        $this->actingAs(planetNine())
            ->delete(route('previews-delete', 99999))
            ->assertStatus(404);
    });
});
