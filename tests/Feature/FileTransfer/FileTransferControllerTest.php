<?php

declare(strict_types=1);

namespace Tests\Feature\FileTransfer;

use App\Models\User;
use App\Models\FileTransfer;
use App\Models\Client;
use App\Services\VirusScanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Mockery;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->client = Client::factory()->create();
    Storage::fake('local');

    // Mock virus scanning service
    $this->virusScanner = Mockery::mock(VirusScanService::class);
    $this->app->instance(VirusScanService::class, $this->virusScanner);
});

describe('FileTransferController Security', function () {
    it('requires authentication for file operations', function () {
        $this->post(route('file-transfer.upload'))
            ->assertRedirect(route('login'));

        $this->get(route('file-transfer.index'))
            ->assertRedirect(route('login'));
    });

    it('scans uploaded files for viruses', function () {
        $file = UploadedFile::fake()->create('document.pdf', 1024);

        $this->virusScanner
            ->shouldReceive('scanFile')
            ->once()
            ->with(Mockery::type('string'))
            ->andReturn(['clean' => true, 'message' => 'File is clean']);

        $this->actingAs($this->user)
            ->post(route('file-transfer.upload'), [
                'file' => $file,
                'client_id' => $this->client->id,
                'description' => 'Test file upload'
            ])
            ->assertRedirect()
            ->assertSessionHas('success');
    });

    it('quarantines infected files', function () {
        $file = UploadedFile::fake()->create('infected.exe', 1024);

        $this->virusScanner
            ->shouldReceive('scanFile')
            ->once()
            ->andReturn(['clean' => false, 'message' => 'VIRUS DETECTED: Malware.Generic']);

        $this->actingAs($this->user)
            ->post(route('file-transfer.upload'), [
                'file' => $file,
                'client_id' => $this->client->id,
                'description' => 'Test file upload'
            ])
            ->assertRedirect()
            ->assertSessionHas('error', 'File upload failed: VIRUS DETECTED: Malware.Generic');

        $this->assertDatabaseMissing('file_transfers', [
            'original_name' => 'infected.exe'
        ]);
    });
});

describe('FileTransferController Upload', function () {
    it('uploads file with valid data', function () {
        $file = UploadedFile::fake()->create('document.pdf', 1024, 'application/pdf');

        $this->virusScanner
            ->shouldReceive('scanFile')
            ->once()
            ->andReturn(['clean' => true, 'message' => 'File is clean']);

        $data = [
            'file' => $file,
            'client_id' => $this->client->id,
            'description' => 'Important document',
            'password_protected' => true,
            'password' => 'securepassword'
        ];

        $this->actingAs($this->user)
            ->post(route('file-transfer.upload'), $data)
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('file_transfers', [
            'original_name' => 'document.pdf',
            'client_id' => $this->client->id,
            'user_id' => $this->user->id,
            'description' => 'Important document',
            'password_protected' => true
        ]);
    });

    it('validates file size limits', function () {
        $largeFile = UploadedFile::fake()->create('huge.zip', 102400); // 100MB

        $this->actingAs($this->user)
            ->post(route('file-transfer.upload'), [
                'file' => $largeFile,
                'client_id' => $this->client->id
            ])
            ->assertSessionHasErrors(['file']);
    });

    it('validates file types', function () {
        $invalidFile = UploadedFile::fake()->create('script.bat', 1024, 'application/x-bat');

        $this->actingAs($this->user)
            ->post(route('file-transfer.upload'), [
                'file' => $invalidFile,
                'client_id' => $this->client->id
            ])
            ->assertSessionHasErrors(['file']);
    });

    it('generates unique download tokens', function () {
        $file = UploadedFile::fake()->create('document.pdf', 1024);

        $this->virusScanner
            ->shouldReceive('scanFile')
            ->once()
            ->andReturn(['clean' => true, 'message' => 'File is clean']);

        $this->actingAs($this->user)
            ->post(route('file-transfer.upload'), [
                'file' => $file,
                'client_id' => $this->client->id
            ])
            ->assertRedirect();

        $transfer = FileTransfer::where('original_name', 'document.pdf')->first();
        expect($transfer->download_token)->toBeString()->toHaveLength(32);
        expect($transfer->download_url)->toContain($transfer->download_token);
    });
});

describe('FileTransferController Download', function () {
    it('allows download with valid token', function () {
        $transfer = FileTransfer::factory()->create([
            'user_id' => $this->user->id,
            'client_id' => $this->client->id
        ]);

        // Create actual file
        Storage::disk('local')->put($transfer->file_path, 'file content');

        $this->get(route('file-transfer.download', $transfer->download_token))
            ->assertOk()
            ->assertHeader('Content-Disposition', 'attachment; filename="' . $transfer->original_name . '"');
    });

    it('requires password for protected files', function () {
        $transfer = FileTransfer::factory()->create([
            'password_protected' => true,
            'password' => bcrypt('secret123')
        ]);

        $this->get(route('file-transfer.download', $transfer->download_token))
            ->assertStatus(200) // Should show password form
            ->assertSee('Password Required');
    });

    it('tracks download attempts', function () {
        $transfer = FileTransfer::factory()->create();
        Storage::disk('local')->put($transfer->file_path, 'file content');

        $this->get(route('file-transfer.download', $transfer->download_token));

        $transfer->refresh();
        expect($transfer->download_count)->toBe(1);
        expect($transfer->last_downloaded_at)->not()->toBeNull();
    });

    it('verifies file cleanup functionality', function () {
        $transfer = FileTransfer::factory()->create([
            'max_downloads' => 2,
            'download_count' => 2
        ]);

        $this->get(route('file-transfer.download', $transfer->download_token))
            ->assertStatus(410)
            ->assertSee('Download limit exceeded');
    });
});

describe('FileTransferController Management', function () {
    it('lists user transfers with pagination', function () {
        FileTransfer::factory(15)->create(['user_id' => $this->user->id]);
        FileTransfer::factory(5)->create(); // Other users' files

        $this->actingAs($this->user)
            ->get(route('file-transfer.index'))
            ->assertOk()
            ->assertInertia(
                fn($page) => $page
                    ->component('FileTransfer/Index')
                    ->has('transfers.data', 10) // Default pagination
                    ->where('transfers.total', 15)
            );
    });

    it('filters transfers by client', function () {
        $client1 = Client::factory()->create();
        $client2 = Client::factory()->create();

        FileTransfer::factory(3)->create(['user_id' => $this->user->id, 'client_id' => $client1->id]);
        FileTransfer::factory(2)->create(['user_id' => $this->user->id, 'client_id' => $client2->id]);

        $this->actingAs($this->user)
            ->get(route('file-transfer.index', ['client' => $client1->id]))
            ->assertOk()
            ->assertInertia(
                fn($page) => $page
                    ->has('transfers.data', 3)
            );
    });

    it('deletes expired transfers automatically', function () {
        $expiredTransfer = FileTransfer::factory()->create([
            'user_id' => $this->user->id
        ]);

        $validTransfer = FileTransfer::factory()->create([
            'user_id' => $this->user->id
        ]);

        $this->actingAs($this->user)
            ->post(route('file-transfer.cleanup'))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('file_transfers', ['id' => $expiredTransfer->id]);
        $this->assertDatabaseHas('file_transfers', ['id' => $validTransfer->id]);
    });
});
