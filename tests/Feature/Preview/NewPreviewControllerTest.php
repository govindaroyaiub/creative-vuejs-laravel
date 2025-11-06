<?php

declare(strict_types=1);

namespace Tests\Feature\Preview;

use App\Models\User;
use App\Models\newPreview;
use App\Models\Client;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->client = Client::factory()->create();
    Storage::fake('public');
});

describe('NewPreviewController Authentication', function () {
    it('requires authentication for all routes', function () {
        $this->get(route('new-preview.index'))
            ->assertRedirect(route('login'));

        $this->post(route('new-preview.store'))
            ->assertRedirect(route('login'));
    });
});

describe('NewPreviewController Index', function () {
    it('displays preview index page for authenticated users', function () {
        $this->actingAs($this->user)
            ->get(route('new-preview.index'))
            ->assertOk()
            ->assertInertia(
                fn($page) => $page
                    ->component('Preview/Index')
                    ->has('previews')
                    ->has('clients')
            );
    });

    it('filters previews by client when client parameter is provided', function () {
        $preview1 = newPreview::factory()->create(['client_id' => $this->client->id]);
        $preview2 = newPreview::factory()->create();

        $this->actingAs($this->user)
            ->get(route('new-preview.index', ['client' => $this->client->id]))
            ->assertOk()
            ->assertInertia(
                fn($page) => $page
                    ->where('previews.data.0.id', $preview1->id)
                    ->where('previews.data', fn($previews) => count($previews) === 1)
            );
    });

    it('searches previews by title', function () {
        $preview1 = newPreview::factory()->create(['title' => 'Laravel Testing']);
        $preview2 = newPreview::factory()->create(['title' => 'Vue.js Development']);

        $this->actingAs($this->user)
            ->get(route('new-preview.index', ['search' => 'Laravel']))
            ->assertOk()
            ->assertInertia(
                fn($page) => $page
                    ->where('previews.data.0.id', $preview1->id)
                    ->where('previews.data', fn($previews) => count($previews) === 1)
            );
    });
});

describe('NewPreviewController Store', function () {
    it('creates a new preview with valid data', function () {
        $file = UploadedFile::fake()->create('preview.mp4', 1024, 'video/mp4');

        $data = [
            'title' => 'Test Preview',
            'description' => 'Test Description',
            'client_id' => $this->client->id,
            'video_file' => $file,
            'duration' => '00:02:30',
            'resolution' => '1920x1080',
            'fps' => 30
        ];

        $this->actingAs($this->user)
            ->post(route('new-preview.store'), $data)
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('new_previews', [
            'title' => 'Test Preview',
            'description' => 'Test Description',
            'client_id' => $this->client->id,
            'user_id' => $this->user->id
        ]);

        expect(Storage::disk('public')->exists('previews/' . $file->hashName()))->toBeTrue();
    });

    it('validates required fields', function () {
        $this->actingAs($this->user)
            ->post(route('new-preview.store'), [])
            ->assertSessionHasErrors(['title', 'client_id', 'video_file']);
    });

    it('validates video file type and size', function () {
        $invalidFile = UploadedFile::fake()->create('document.pdf', 1024, 'application/pdf');

        $this->actingAs($this->user)
            ->post(route('new-preview.store'), [
                'title' => 'Test Preview',
                'client_id' => $this->client->id,
                'video_file' => $invalidFile
            ])
            ->assertSessionHasErrors(['video_file']);
    });

    it('handles large file uploads within limits', function () {
        $largeFile = UploadedFile::fake()->create('large_preview.mp4', 50000, 'video/mp4'); // 50MB

        $data = [
            'title' => 'Large Preview',
            'client_id' => $this->client->id,
            'video_file' => $largeFile
        ];

        $this->actingAs($this->user)
            ->post(route('new-preview.store'), $data)
            ->assertRedirect()
            ->assertSessionHas('success');
    });
});

describe('NewPreviewController Show', function () {
    it('displays preview details for authorized user', function () {
        $preview = newPreview::factory()->create(['user_id' => $this->user->id]);

        $this->actingAs($this->user)
            ->get(route('new-preview.show', $preview))
            ->assertOk()
            ->assertInertia(
                fn($page) => $page
                    ->component('Preview/Show')
                    ->where('preview.id', $preview->id)
            );
    });

    it('prevents unauthorized access to other users previews', function () {
        $otherUser = User::factory()->create();
        $preview = newPreview::factory()->create(['user_id' => $otherUser->id]);

        $this->actingAs($this->user)
            ->get(route('new-preview.show', $preview))
            ->assertForbidden();
    });
});

describe('NewPreviewController Update', function () {
    it('updates preview with valid data', function () {
        $preview = newPreview::factory()->create(['user_id' => $this->user->id]);

        $updateData = [
            'title' => 'Updated Title',
            'description' => 'Updated Description'
        ];

        $this->actingAs($this->user)
            ->put(route('new-preview.update', $preview), $updateData)
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('new_previews', [
            'id' => $preview->id,
            'title' => 'Updated Title',
            'description' => 'Updated Description'
        ]);
    });

    it('prevents unauthorized users from updating previews', function () {
        $otherUser = User::factory()->create();
        $preview = newPreview::factory()->create(['user_id' => $otherUser->id]);

        $this->actingAs($this->user)
            ->put(route('new-preview.update', $preview), ['title' => 'Hacked'])
            ->assertForbidden();
    });
});

describe('NewPreviewController Delete', function () {
    it('deletes preview and associated files', function () {
        $preview = newPreview::factory()->create(['user_id' => $this->user->id]);
        $filePath = 'previews/test-file.mp4';

        // Create a fake file
        Storage::disk('public')->put($filePath, 'fake content');
        $preview->update(['file_path' => $filePath]);

        $this->actingAs($this->user)
            ->delete(route('new-preview.destroy', $preview))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('new_previews', ['id' => $preview->id]);
        expect(Storage::disk('public')->exists($filePath))->toBeFalse();
    });

    it('prevents unauthorized deletion', function () {
        $otherUser = User::factory()->create();
        $preview = newPreview::factory()->create(['user_id' => $otherUser->id]);

        $this->actingAs($this->user)
            ->delete(route('new-preview.destroy', $preview))
            ->assertForbidden();

        $this->assertDatabaseHas('new_previews', ['id' => $preview->id]);
    });
});

describe('NewPreviewController API Endpoints', function () {
    it('returns preview data as JSON for API requests', function () {
        $preview = newPreview::factory()->create(['user_id' => $this->user->id]);

        $this->actingAs($this->user)
            ->getJson(route('api.new-preview.show', $preview))
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'title',
                    'description',
                    'file_path',
                    'created_at'
                ]
            ]);
    });

    it('handles bulk operations', function () {
        $previews = newPreview::factory(3)->create(['user_id' => $this->user->id]);
        $previewIds = $previews->pluck('id')->toArray();

        $this->actingAs($this->user)
            ->postJson(route('api.new-preview.bulk-delete'), [
                'preview_ids' => $previewIds
            ])
            ->assertOk()
            ->assertJson(['message' => 'Previews deleted successfully']);

        foreach ($previewIds as $id) {
            $this->assertDatabaseMissing('new_previews', ['id' => $id]);
        }
    });
});
