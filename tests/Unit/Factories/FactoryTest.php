<?php

declare(strict_types=1);

namespace Tests\Unit\Factories;

use App\Models\Bill;
use App\Models\BillDocument;
use App\Models\Client;
use App\Models\ColorPalette;
use App\Models\Designation;
use App\Models\FileTransfer;
use App\Models\newPreview;
use App\Models\SubBill;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * These factories previously described a different application — `bills` was
 * given `bill_number`/`tax_rate`/`status`, `file_transfers` was given
 * `mime_type`/`download_token`. None of those columns exist, so every factory
 * threw on insert. These tests pin each one to the real schema.
 */
it('creates a colour palette', function () {
    $palette = ColorPalette::factory()->create();

    expect($palette->exists)->toBeTrue();
    expect($palette->primary)->toStartWith('#');
    expect($palette->status)->toBeTruthy();
    expect(ColorPalette::factory()->inactive()->create()->status)->toBeFalsy();
});

it('creates a client with its required palette', function () {
    $client = Client::factory()->create();

    expect($client->exists)->toBeTrue();
    expect($client->color_palette_id)->not->toBeNull();
    expect($client->colorPalette)->toBeInstanceOf(ColorPalette::class);
});

it('creates a bill matching the real thin schema', function () {
    $bill = Bill::factory()->create();

    expect($bill->exists)->toBeTrue();
    expect($bill->name)->toBeString();
    expect($bill->client)->toBeString();
    expect((float) $bill->total_amount)->toBeGreaterThan(0.0);
});

it('creates sub bills and documents against a bill', function () {
    $bill = Bill::factory()->create();

    SubBill::factory(3)->create(['bill_id' => $bill->id]);
    BillDocument::factory(2)->create(['bill_id' => $bill->id]);

    expect($bill->subBills)->toHaveCount(3);
    expect($bill->documents)->toHaveCount(2);
});

it('creates a file transfer with a uuid slug and a public-relative path', function () {
    $transfer = FileTransfer::factory()->create();

    expect($transfer->exists)->toBeTrue();
    expect($transfer->slug)->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-/');
    expect($transfer->file_path)->toStartWith('Transfer Files/');
    expect($transfer->preview_id)->toBeNull();
    expect($transfer->user)->toBeInstanceOf(User::class);
});

it('creates a multi-file transfer as a comma joined path list', function () {
    $transfer = FileTransfer::factory()->withFiles(3)->create();

    expect(explode(',', $transfer->file_path))->toHaveCount(3);
});

it('creates a preview wired to a client, header logo and uploader', function () {
    $preview = newPreview::factory()->create();

    expect($preview->exists)->toBeTrue();
    expect($preview->client_id)->not->toBeNull();
    // header_logo_id must resolve against `clients`, per the controller's
    // `exists:clients,id` rule.
    expect(Client::find($preview->header_logo_id))->not->toBeNull();
    expect($preview->team_members)->toBeArray();
});

it('reuses one client for owner and header logo when asked', function () {
    $client = Client::factory()->create();
    $preview = newPreview::factory()->forClient($client)->create();

    expect($preview->client_id)->toBe($client->id);
    expect($preview->header_logo_id)->toBe($client->id);
});

it('creates a preview owned by a given uploader', function () {
    $user = User::factory()->create();
    $preview = newPreview::factory()->uploadedBy($user)->create();

    expect($preview->uploader_id)->toBe($user->id);
});

it('builds users whose designation relation can be eager loaded', function () {
    // `designation` is both the relation name and its foreign key column, so a
    // factory user missing the attribute used to throw on load().
    $user = User::factory()->create();

    $user->load(['designation', 'client']);

    expect($user->getRelations())->toHaveKey('designation');
    expect($user->designation)->toBeNull();
});

it('attaches a real designation on request', function () {
    $designation = Designation::factory()->create(['name' => 'Art Director']);
    $user = User::factory()->withDesignation($designation)->create();

    $user->load('designation');

    // Careful: `$user->designation` is the foreign key column, not the related
    // model — the attribute shadows the same-named relation. The loaded record
    // is only reachable through getRelation().
    expect($user->designation)->toBe($designation->id);
    expect($user->getRelation('designation'))->toBeInstanceOf(Designation::class);
    expect($user->getRelation('designation')->name)->toBe('Art Director');
});

it('builds an admin who can pass the permission middleware', function () {
    $user = User::factory()->admin()->create();

    expect($user->permissions)->toBe(['*']);
    expect($user->canAccess('/anything'))->toBeTrue();
});
