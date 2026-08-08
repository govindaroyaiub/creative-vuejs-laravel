<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Bill;
use App\Models\BillDocument;
use App\Models\Client;
use App\Models\ColorPalette;
use App\Models\Designation;
use App\Models\FileTransfer;
use App\Models\SubBill;
use App\Models\User;
use App\Models\newCategory;
use App\Models\newPreview;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * This file previously described a different application — `User::active()`,
 * `User::totalRevenue()`, soft-deleted previews with `title`/`duration`
 * columns, `Bill::paid()`. None of it exists. It now covers the relationships
 * and behaviour the models actually have.
 */
describe('User', function () {
    it('belongs to a client', function () {
        $client = Client::factory()->create();
        $user = User::factory()->create(['client_id' => $client->id]);

        expect($user->client)->toBeInstanceOf(Client::class);
        expect($user->client->id)->toBe($client->id);
    });

    it('belongs to a designation through the same-named column', function () {
        $designation = Designation::factory()->create(['name' => 'Motion Designer']);
        $user = User::factory()->withDesignation($designation)->create();

        // The relation is shadowed by the foreign key column of the same name,
        // so the record is only reachable through the relation itself.
        expect($user->designation)->toBe($designation->id);
        expect($user->designation()->first()->name)->toBe('Motion Designer');
    });

    it('survives its client being deleted', function () {
        $client = Client::factory()->create();
        $user = User::factory()->create(['client_id' => $client->id]);

        $client->delete();

        // onDelete('set null') — the user stays, unattached.
        expect($user->fresh()->client_id)->toBeNull();
    });

    it('grants access by wildcard permission', function () {
        $user = User::factory()->create(['permissions' => ['*']]);

        expect($user->canAccess('/previews'))->toBeTrue();
        expect($user->canAccess('/anything-at-all'))->toBeTrue();
    });

    it('grants access only to listed routes otherwise', function () {
        $user = User::factory()->create(['permissions' => ['/previews', '/bills']]);

        expect($user->canAccess('/previews'))->toBeTrue();
        expect($user->canAccess('/file-transfers'))->toBeFalse();
    });

    it('refuses access when permissions are not an array', function () {
        $user = User::factory()->create(['permissions' => null]);

        expect($user->canAccess('/previews'))->toBeFalse();
    });

    it('casts permissions and hides the password', function () {
        $user = User::factory()->create(['permissions' => ['/bills']]);

        expect($user->permissions)->toBeArray();
        expect($user->toArray())->not->toHaveKey('password');
        expect($user->toArray())->not->toHaveKey('remember_token');
    });
});

describe('newPreview', function () {
    it('belongs to a client and an uploader', function () {
        $client = Client::factory()->create();
        $uploader = User::factory()->create();
        $preview = newPreview::factory()->forClient($client)->uploadedBy($uploader)->create();

        expect($preview->client->id)->toBe($client->id);
        expect($preview->uploader->id)->toBe($uploader->id);
    });

    it('belongs to a colour palette', function () {
        $preview = newPreview::factory()->create();

        expect($preview->colorPalette)->toBeInstanceOf(ColorPalette::class);
    });

    it('has many categories', function () {
        $preview = newPreview::factory()->create();

        newCategory::create(['preview_id' => $preview->id, 'name' => 'Banners', 'type' => 'banner']);
        newCategory::create(['preview_id' => $preview->id, 'name' => 'Videos', 'type' => 'video']);

        expect($preview->categories)->toHaveCount(2);
        expect($preview->categories->pluck('type')->all())->toBe(['banner', 'video']);
    });

    it('casts team members to an array', function () {
        $members = [1, 2, 3];
        $preview = newPreview::factory()->withTeam($members)->create();

        expect($preview->fresh()->team_members)->toBe($members);
    });

    it('has a unique slug', function () {
        $preview = newPreview::factory()->create();

        expect(fn () => newPreview::factory()->create(['slug' => $preview->slug]))
            ->toThrow(\Illuminate\Database\QueryException::class);
    });

    it('cascades its categories away when deleted', function () {
        $preview = newPreview::factory()->create();
        $category = newCategory::create([
            'preview_id' => $preview->id, 'name' => 'Banners', 'type' => 'banner',
        ]);

        $preview->delete();

        expect(newCategory::find($category->id))->toBeNull();
    });
});

describe('Bill', function () {
    it('has many sub bills', function () {
        $bill = Bill::factory()->create();
        SubBill::factory(3)->create(['bill_id' => $bill->id]);

        expect($bill->subBills)->toHaveCount(3);
        // Lazy loading is disabled app-wide, so the inverse has to be loaded.
        expect($bill->subBills->first()->load('bill')->bill->id)->toBe($bill->id);
    });

    it('has many documents', function () {
        $bill = Bill::factory()->create();
        BillDocument::factory(2)->create(['bill_id' => $bill->id]);

        expect($bill->documents)->toHaveCount(2);
    });

    it('cascades sub bills and documents away when deleted', function () {
        $bill = Bill::factory()->create();
        $sub = SubBill::factory()->create(['bill_id' => $bill->id]);
        $doc = BillDocument::factory()->create(['bill_id' => $bill->id]);

        $bill->delete();

        expect(SubBill::find($sub->id))->toBeNull();
        expect(BillDocument::find($doc->id))->toBeNull();
    });

    it('stores the client as free text, not a relation', function () {
        $bill = Bill::factory()->create(['client' => 'Nike']);

        expect($bill->client)->toBe('Nike');
        expect($bill->getFillable())->toContain('client');
        expect($bill->getFillable())->not->toContain('client_id');
    });
});

describe('BillDocument', function () {
    it('belongs to its bill and its uploader', function () {
        $uploader = User::factory()->create();
        $doc = BillDocument::factory()->create(['uploaded_by' => $uploader->id]);

        expect($doc->bill)->toBeInstanceOf(Bill::class);
        expect($doc->uploader->id)->toBe($uploader->id);
    });

    it('formats its file size for display', function () {
        $doc = BillDocument::factory()->create(['file_size' => 2097152]);

        expect($doc->formatted_file_size)->toContain('MB');
    });
});

describe('SubBill', function () {
    it('keeps quantity, unit price and amount consistent', function () {
        $sub = SubBill::factory()->create([
            'quantity' => 4,
            'unit_price' => 250.00,
            'amount' => 1000.00,
        ]);

        expect((float) $sub->amount)->toBe((float) $sub->quantity * (float) $sub->unit_price);
    });
});

describe('FileTransfer', function () {
    it('belongs to the user who created it', function () {
        $user = User::factory()->create();
        $transfer = FileTransfer::factory()->forUser($user)->create();

        expect($transfer->user)->toBeInstanceOf(User::class);
        expect($transfer->user->id)->toBe($user->id);
    });

    it('stores its slug as a uuid so the public link is unguessable', function () {
        $transfer = FileTransfer::factory()->create();

        expect($transfer->slug)->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/');
    });

    it('holds several archives as one comma joined path string', function () {
        $transfer = FileTransfer::factory()->withFiles(3)->create();

        expect(explode(',', $transfer->file_path))->toHaveCount(3);
        expect($transfer->file_path)->toContain('Transfer Files/');
    });

    it('links to a preview only once a round is approved', function () {
        $preview = newPreview::factory()->create();

        expect(FileTransfer::factory()->create()->preview_id)->toBeNull();
        expect(FileTransfer::factory()->forPreview($preview->id)->create()->preview_id)
            ->toBe($preview->id);
    });
});

describe('Client', function () {
    it('belongs to a colour palette and has many users', function () {
        $client = Client::factory()->create();
        User::factory(2)->create(['client_id' => $client->id]);

        expect($client->colorPalette)->toBeInstanceOf(ColorPalette::class);
        expect($client->users)->toHaveCount(2);
    });

    it('limits the validColumns scope to real columns', function () {
        Client::factory()->create();

        $client = Client::validColumns()->first();

        expect($client->getAttributes())->toHaveKeys(
            ['id', 'name', 'website', 'preview_url', 'logo', 'color_palette_id']
        );
    });
});
