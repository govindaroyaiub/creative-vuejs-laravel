<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\User;
use App\Models\newPreview;
use App\Models\Client;
use App\Models\Bill;
use App\Models\FileTransfer;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('User Model', function () {
    it('has many previews relationship', function () {
        $user = User::factory()->create();
        $preview = newPreview::factory()->create(['user_id' => $user->id]);

        expect($user->previews)->toHaveCount(1);
        expect($user->previews->first()->id)->toBe($preview->id);
    });

    it('has many bills relationship', function () {
        $user = User::factory()->create();
        $bill = Bill::factory()->create(['user_id' => $user->id]);

        expect($user->bills)->toHaveCount(1);
        expect($user->bills->first()->id)->toBe($bill->id);
    });

    it('has many file transfers relationship', function () {
        $user = User::factory()->create();
        $transfer = FileTransfer::factory()->create(['user_id' => $user->id]);

        expect($user->fileTransfers)->toHaveCount(1);
        expect($user->fileTransfers->first()->id)->toBe($transfer->id);
    });

    it('scopes active users correctly', function () {
        $activeUser = User::factory()->create(['email_verified_at' => now()]);
        $inactiveUser = User::factory()->create(['email_verified_at' => null]);

        $activeUsers = User::active()->get();

        expect($activeUsers)->toHaveCount(1);
        expect($activeUsers->first()->id)->toBe($activeUser->id);
    });

    it('calculates total revenue for user', function () {
        $user = User::factory()->create();

        Bill::factory()->create([
            'user_id' => $user->id,
            'status' => 'paid',
            'total_amount' => 1000.00
        ]);

        Bill::factory()->create([
            'user_id' => $user->id,
            'status' => 'paid',
            'total_amount' => 500.00
        ]);

        Bill::factory()->create([
            'user_id' => $user->id,
            'status' => 'pending',
            'total_amount' => 300.00
        ]);

        expect($user->totalRevenue())->toBe(1500.00);
    });

    it('gets user activity summary', function () {
        $user = User::factory()->create();

        newPreview::factory(3)->create(['user_id' => $user->id]);
        Bill::factory(2)->create(['user_id' => $user->id]);
        FileTransfer::factory(1)->create(['user_id' => $user->id]);

        $summary = $user->activitySummary();

        expect($summary)->toHaveKeys(['previews_count', 'bills_count', 'transfers_count']);
        expect($summary['previews_count'])->toBe(3);
        expect($summary['bills_count'])->toBe(2);
        expect($summary['transfers_count'])->toBe(1);
    });
});

describe('newPreview Model', function () {
    it('belongs to user and client', function () {
        $user = User::factory()->create();
        $client = Client::factory()->create();
        $preview = newPreview::factory()->create([
            'user_id' => $user->id,
            'client_id' => $client->id
        ]);

        expect($preview->user->id)->toBe($user->id);
        expect($preview->client->id)->toBe($client->id);
    });

    it('has correct fillable attributes', function () {
        $preview = new newPreview();

        expect($preview->getFillable())->toContain('title');
        expect($preview->getFillable())->toContain('description');
        expect($preview->getFillable())->toContain('file_path');
        expect($preview->getFillable())->toContain('user_id');
        expect($preview->getFillable())->toContain('client_id');
    });

    it('scopes by status correctly', function () {
        newPreview::factory()->create(['status' => 'active']);
        newPreview::factory()->create(['status' => 'inactive']);
        newPreview::factory()->create(['status' => 'active']);

        $activePreviews = newPreview::active()->get();

        expect($activePreviews)->toHaveCount(2);
        $activePreviews->each(fn($preview) => expect($preview->status)->toBe('active'));
    });

    it('generates file URL correctly', function () {
        $preview = newPreview::factory()->create([
            'file_path' => 'previews/test-video.mp4'
        ]);

        expect($preview->file_url)->toContain('storage/previews/test-video.mp4');
    });

    it('calculates duration in human readable format', function () {
        $preview = newPreview::factory()->create(['duration' => '00:02:30']);

        expect($preview->human_duration)->toBe('2 minutes 30 seconds');
    });

    it('soft deletes correctly', function () {
        $preview = newPreview::factory()->create();
        $previewId = $preview->id;

        $preview->delete();

        expect(newPreview::find($previewId))->toBeNull();
        expect(newPreview::withTrashed()->find($previewId))->not()->toBeNull();
    });
});

describe('Bill Model', function () {
    it('belongs to user and client', function () {
        $user = User::factory()->create();
        $client = Client::factory()->create();
        $bill = Bill::factory()->create([
            'user_id' => $user->id,
            'client_id' => $client->id
        ]);

        expect($bill->user->id)->toBe($user->id);
        expect($bill->client->id)->toBe($client->id);
    });

    it('has many sub bills', function () {
        $bill = Bill::factory()->create();
        $subBills = \App\Models\SubBill::factory(3)->create(['bill_id' => $bill->id]);

        expect($bill->subBills)->toHaveCount(3);
    });

    it('scopes by status correctly', function () {
        Bill::factory()->create(['status' => 'paid']);
        Bill::factory()->create(['status' => 'pending']);
        Bill::factory()->create(['status' => 'overdue']);

        $paidBills = Bill::paid()->get();
        $pendingBills = Bill::pending()->get();

        expect($paidBills)->toHaveCount(1);
        expect($pendingBills)->toHaveCount(1);
    });

    it('calculates totals correctly', function () {
        $bill = Bill::factory()->create([
            'subtotal' => 1000.00,
            'tax_rate' => 10.00
        ]);

        expect($bill->tax_amount)->toBe(100.00);
        expect($bill->total_amount)->toBe(1100.00);
    });

    it('determines if bill is overdue', function () {
        $overdueBill = Bill::factory()->create([
            'due_date' => now()->subDays(5),
            'status' => 'pending'
        ]);

        $currentBill = Bill::factory()->create([
            'due_date' => now()->addDays(5),
            'status' => 'pending'
        ]);

        expect($overdueBill->is_overdue)->toBeTrue();
        expect($currentBill->is_overdue)->toBeFalse();
    });

    it('formats bill number correctly', function () {
        $bill = Bill::factory()->create(['bill_number' => 'INV-2024-001']);

        expect($bill->formatted_bill_number)->toBe('INV-2024-001');
    });
});

describe('FileTransfer Model', function () {
    it('belongs to user and client', function () {
        $user = User::factory()->create();
        $client = Client::factory()->create();
        $transfer = FileTransfer::factory()->create([
            'user_id' => $user->id,
            'client_id' => $client->id
        ]);

        expect($transfer->user->id)->toBe($user->id);
        expect($transfer->client->id)->toBe($client->id);
    });

    it('generates unique download token', function () {
        $transfer1 = FileTransfer::factory()->create();
        $transfer2 = FileTransfer::factory()->create();

        expect($transfer1->download_token)->not()->toBe($transfer2->download_token);
        expect($transfer1->download_token)->toHaveLength(32);
    });

    it('generates download URL correctly', function () {
        $transfer = FileTransfer::factory()->create();

        expect($transfer->download_url)->toContain('file-transfer/download/');
        expect($transfer->download_url)->toContain($transfer->download_token);
    });

    it('determines if file is expired', function () {
        $expiredTransfer = FileTransfer::factory()->create([
            'expires_at' => now()->subDay()
        ]);

        $validTransfer = FileTransfer::factory()->create([
            'expires_at' => now()->addDay()
        ]);

        expect($expiredTransfer->is_expired)->toBeTrue();
        expect($validTransfer->is_expired)->toBeFalse();
    });

    it('tracks download attempts correctly', function () {
        $transfer = FileTransfer::factory()->create([
            'download_count' => 0,
            'max_downloads' => 5
        ]);

        $transfer->incrementDownloadCount();

        expect($transfer->download_count)->toBe(1);
        expect($transfer->last_downloaded_at)->not()->toBeNull();
    });

    it('determines download availability', function () {
        $availableTransfer = FileTransfer::factory()->create([
            'download_count' => 2,
            'max_downloads' => 5,
            'expires_at' => now()->addWeek()
        ]);

        $exhaustedTransfer = FileTransfer::factory()->create([
            'download_count' => 5,
            'max_downloads' => 5
        ]);

        expect($availableTransfer->is_download_available)->toBeTrue();
        expect($exhaustedTransfer->is_download_available)->toBeFalse();
    });

    it('formats file size correctly', function () {
        $transfer = FileTransfer::factory()->create(['file_size' => 1048576]); // 1MB

        expect($transfer->formatted_file_size)->toBe('1.00 MB');
    });
});
