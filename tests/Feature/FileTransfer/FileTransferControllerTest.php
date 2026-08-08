<?php

declare(strict_types=1);

namespace Tests\Feature\FileTransfer;

use App\Models\FileTransfer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;

uses(RefreshDatabase::class);

/**
 * Rewritten against the real controller. The previous version called
 * routes that were never defined (`file-transfer.upload`, `.download`,
 * `.cleanup`) and assumed columns this table does not have.
 *
 * Uploads land in `public/Transfer Files`, not on a Storage disk, so the
 * directory is swept between tests instead of faked.
 */
beforeEach(function () {
    $this->user = User::factory()->admin()->create();
    $this->transferDir = public_path('Transfer Files');
    $this->before = File::exists($this->transferDir) ? File::files($this->transferDir) : [];
});

afterEach(function () {
    // Remove only what this test created, leaving any pre-existing files.
    if (! File::exists($this->transferDir)) {
        return;
    }
    $keep = collect($this->before)->map->getFilename()->all();
    foreach (File::files($this->transferDir) as $file) {
        if (! in_array($file->getFilename(), $keep, true)) {
            File::delete($file->getPathname());
        }
    }
});

describe('access control', function () {
    it('redirects guests away from the transfer list', function () {
        $this->get(route('file-transfers'))->assertRedirect(route('login'));
    });

    it('redirects guests away from creating a transfer', function () {
        $this->post(route('file-transfers-add-post'))->assertRedirect(route('login'));
    });

    it('refuses a signed-in user with no permissions', function () {
        $this->actingAs(User::factory()->create())
            ->get(route('file-transfers'))
            ->assertStatus(403);
    });

    it('serves the public view by slug without authentication', function () {
        $transfer = FileTransfer::factory()->create();

        $this->get(route('file-transfers-view', $transfer->slug))
            ->assertStatus(200)
            ->assertInertia(fn ($page) => $page->component('FileTransfers/View')
                ->where('fileTransfer.slug', $transfer->slug));
    });

    it('404s an unknown slug', function () {
        $this->get(route('file-transfers-view', 'not-a-real-slug'))->assertStatus(404);
    });
});

describe('listing', function () {
    it('paginates transfers newest first', function () {
        FileTransfer::factory()->create(['name' => 'Older', 'created_at' => now()->subDay()]);
        FileTransfer::factory()->create(['name' => 'Newer', 'created_at' => now()]);

        $this->actingAs($this->user)
            ->get(route('file-transfers'))
            ->assertStatus(200)
            ->assertInertia(fn ($page) => $page->component('FileTransfers/Index')
                ->where('fileTransfers.data.0.name', 'Newer')
                ->where('fileTransfers.data.1.name', 'Older'));
    });

    it('splits the stored path list into an array per transfer', function () {
        FileTransfer::factory()->withFiles(3)->create();

        $this->actingAs($this->user)
            ->get(route('file-transfers'))
            ->assertInertia(fn ($page) => $page->count('fileTransfers.data.0.file_paths', 3));
    });

    it('strips the Transfer Files prefix from the paths it exposes', function () {
        FileTransfer::factory()->create(['file_path' => 'Transfer Files/assets.zip']);

        $this->actingAs($this->user)
            ->get(route('file-transfers'))
            ->assertInertia(fn ($page) => $page->where('fileTransfers.data.0.file_paths.0', 'assets.zip'));
    });

    it('searches by name', function () {
        // `client` is searched with the same LIKE term, and the factory fills it
        // from faker — a company such as "Summers Ltd" would match "Summer" and
        // break the exact-count assertion at random.
        FileTransfer::factory()->create(['name' => 'Summer Campaign', 'client' => 'Qqq Decoy']);
        FileTransfer::factory()->create(['name' => 'Winter Campaign', 'client' => 'Qqq Decoy']);

        $this->actingAs($this->user)
            ->get(route('file-transfers', ['search' => 'Summer']))
            ->assertInertia(fn ($page) => $page->count('fileTransfers.data', 1)
                ->where('fileTransfers.data.0.name', 'Summer Campaign'));
    });

    it('searches by a written-out date', function () {
        // Exercises the driver-aware date expression in SqlDate.
        FileTransfer::factory()->create(['created_at' => '2026-08-07 12:00:00']);
        FileTransfer::factory()->create(['created_at' => '2025-01-02 12:00:00']);

        $this->actingAs($this->user)
            ->get(route('file-transfers', ['search' => 'August 2026']))
            ->assertInertia(fn ($page) => $page->count('fileTransfers.data', 1));
    });
});

describe('creating', function () {
    it('stores an upload and records it against the signed-in user', function () {
        $this->actingAs($this->user)
            ->post(route('file-transfers-add-post'), [
                'name' => 'Final Assets',
                'client' => 'Nike',
                'file' => [UploadedFile::fake()->create('assets.zip', 64)],
            ])
            ->assertRedirect(route('file-transfers'))
            ->assertSessionHas('success');

        $transfer = FileTransfer::sole();

        expect($transfer->name)->toBe('Final Assets');
        expect($transfer->client)->toBe('Nike');
        expect($transfer->user_id)->toBe($this->user->id);
        expect($transfer->slug)->toMatch('/^[0-9a-f]{8}-/');
        expect($transfer->file_path)->toStartWith('Transfer Files/assets_');
        expect(File::exists(public_path($transfer->file_path)))->toBeTrue();
    });

    it('records several uploads as a comma joined list', function () {
        $this->actingAs($this->user)
            ->post(route('file-transfers-add-post'), [
                'name' => 'Bundle',
                'client' => 'Nike',
                'file' => [
                    UploadedFile::fake()->create('one.zip', 8),
                    UploadedFile::fake()->create('two.zip', 8),
                ],
            ])
            ->assertRedirect(route('file-transfers'));

        $paths = explode(',', FileTransfer::sole()->file_path);

        expect($paths)->toHaveCount(2);
        foreach ($paths as $path) {
            expect(File::exists(public_path($path)))->toBeTrue();
        }
    });

    it('requires a name, a client and at least one file', function () {
        $this->actingAs($this->user)
            ->post(route('file-transfers-add-post'), [])
            ->assertSessionHasErrors(['name', 'client', 'file']);

        expect(FileTransfer::count())->toBe(0);
    });

    it('rejects a file over the 20MB limit', function () {
        $this->actingAs($this->user)
            ->post(route('file-transfers-add-post'), [
                'name' => 'Too Big',
                'client' => 'Nike',
                'file' => [UploadedFile::fake()->create('huge.zip', 20481)],
            ])
            ->assertSessionHasErrors('file.0');

        expect(FileTransfer::count())->toBe(0);
    });

    it('gives two uploads of the same filename distinct stored names', function () {
        foreach (['first', 'second'] as $name) {
            $this->actingAs($this->user)->post(route('file-transfers-add-post'), [
                'name' => $name,
                'client' => 'Nike',
                'file' => [UploadedFile::fake()->create('assets.zip', 8)],
            ]);
        }

        $paths = FileTransfer::pluck('file_path');

        expect($paths)->toHaveCount(2);
        expect($paths[0])->not->toBe($paths[1]);
    });
});

describe('updating', function () {
    it('renames a transfer without touching its files', function () {
        $transfer = FileTransfer::factory()->create(['name' => 'Draft', 'client' => 'Nike']);
        $originalPath = $transfer->file_path;

        $this->actingAs($this->user)
            ->post(route('file-transfers-update', $transfer->id), [
                'name' => 'Final',
                'client' => 'Adidas',
            ])
            ->assertRedirect(route('file-transfers'));

        $transfer->refresh();

        expect($transfer->name)->toBe('Final');
        expect($transfer->client)->toBe('Adidas');
        expect($transfer->file_path)->toBe($originalPath);
    });

    it('replaces the files and deletes the old ones when given new uploads', function () {
        // Put a real file on disk so the replacement can be observed removing it.
        $this->actingAs($this->user)->post(route('file-transfers-add-post'), [
            'name' => 'Round 1',
            'client' => 'Nike',
            'file' => [UploadedFile::fake()->create('old.zip', 8)],
        ]);

        $transfer = FileTransfer::sole();
        $oldPath = public_path($transfer->file_path);
        expect(File::exists($oldPath))->toBeTrue();

        $this->actingAs($this->user)->post(route('file-transfers-update', $transfer->id), [
            'name' => 'Round 2',
            'client' => 'Nike',
            'file' => [UploadedFile::fake()->create('new.zip', 8)],
        ])->assertRedirect(route('file-transfers'));

        $transfer->refresh();

        expect(File::exists($oldPath))->toBeFalse();
        expect($transfer->file_path)->toContain('new_');
        expect(File::exists(public_path($transfer->file_path)))->toBeTrue();
    });

    it('requires a name and client on update', function () {
        $transfer = FileTransfer::factory()->create();

        $this->actingAs($this->user)
            ->post(route('file-transfers-update', $transfer->id), [])
            ->assertSessionHasErrors(['name', 'client']);
    });
});

describe('deleting', function () {
    it('deletes a transfer and its files from disk', function () {
        $this->actingAs($this->user)->post(route('file-transfers-add-post'), [
            'name' => 'Doomed',
            'client' => 'Nike',
            'file' => [UploadedFile::fake()->create('bye.zip', 8)],
        ]);

        $transfer = FileTransfer::sole();
        $path = public_path($transfer->file_path);

        $this->actingAs($this->user)->delete(route('file-transfers-delete', $transfer->id));

        expect(FileTransfer::find($transfer->id))->toBeNull();
        expect(File::exists($path))->toBeFalse();
    });

    it('bulk deletes unlinked transfers', function () {
        $ids = FileTransfer::factory(3)->create()->pluck('id')->all();

        $this->actingAs($this->user)
            ->post(route('file-transfers-bulk-delete'), ['ids' => $ids]);

        expect(FileTransfer::whereIn('id', $ids)->count())->toBe(0);
    });

    it('skips transfers that belong to a preview during bulk delete', function () {
        $preview = \App\Models\newPreview::factory()->create();
        $linked = FileTransfer::factory()->forPreview($preview->id)->create();
        $loose = FileTransfer::factory()->create();

        $this->actingAs($this->user)
            ->post(route('file-transfers-bulk-delete'), ['ids' => [$linked->id, $loose->id]]);

        // A transfer attached to a preview is the client's approved delivery —
        // it must not disappear from a list-screen bulk action.
        expect(FileTransfer::find($linked->id))->not->toBeNull();
        expect(FileTransfer::find($loose->id))->toBeNull();
    });

    it('rejects a bulk delete of ids that do not exist', function () {
        $this->actingAs($this->user)
            ->post(route('file-transfers-bulk-delete'), ['ids' => [99999]])
            ->assertSessionHasErrors('ids.0');
    });
});
