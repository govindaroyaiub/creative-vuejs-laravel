<?php

declare(strict_types=1);

namespace Tests\Feature\Billing;

use App\Models\Bill;
use App\Models\BillDocument;
use App\Models\SubBill;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

/**
 * Rewritten against the real controller. The previous version posted to
 * `bills.store` / `bills.index` (the real names are `bills-create-post` /
 * `bills`) and assumed a `bills` table with tax, status and due dates. The
 * real table is name + client + total_amount, with line items in `sub_bills`.
 */
beforeEach(function () {
    $this->user = User::factory()->admin()->create();
    Storage::fake('public');
});

/** The controller requires at least one line item on every write. */
function billPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'INV-2026-001',
        'client' => 'Nike',
        'bill_date' => '2026-08-07',
        'total_amount' => 1500.00,
        'sub_bills' => [
            ['item' => 'Banner set', 'quantity' => 3, 'unit_price' => 400.00, 'amount' => 1200.00],
            ['item' => 'Revisions', 'quantity' => 1, 'unit_price' => 300.00, 'amount' => 300.00],
        ],
    ], $overrides);
}

describe('access control', function () {
    it('redirects guests to login', function () {
        $this->get(route('bills'))->assertRedirect(route('login'));
        $this->post(route('bills-create-post'))->assertRedirect(route('login'));
    });

    it('refuses a signed-in user with no permissions', function () {
        $this->actingAs(User::factory()->create())
            ->get(route('bills'))
            ->assertStatus(403);
    });
});

describe('listing', function () {
    it('lists bills oldest first', function () {
        Bill::factory()->create(['name' => 'Second', 'created_at' => now()]);
        Bill::factory()->create(['name' => 'First', 'created_at' => now()->subMonth()]);

        $this->actingAs($this->user)
            ->get(route('bills'))
            ->assertStatus(200)
            ->assertInertia(fn ($page) => $page->component('Bills/Index')
                ->where('bills.data.0.name', 'First')
                ->where('bills.data.1.name', 'Second'));
    });

    it('searches by name, client and amount', function () {
        Bill::factory()->create(['name' => 'INV-100', 'client' => 'Nike', 'total_amount' => 900.00]);
        Bill::factory()->create(['name' => 'INV-200', 'client' => 'Adidas', 'total_amount' => 4200.00]);

        $this->actingAs($this->user)->get(route('bills', ['search' => 'Adidas']))
            ->assertInertia(fn ($page) => $page->count('bills.data', 1)
                ->where('bills.data.0.name', 'INV-200'));

        $this->actingAs($this->user)->get(route('bills', ['search' => 'INV-100']))
            ->assertInertia(fn ($page) => $page->count('bills.data', 1));

        $this->actingAs($this->user)->get(route('bills', ['search' => '4200']))
            ->assertInertia(fn ($page) => $page->count('bills.data', 1)
                ->where('bills.data.0.client', 'Adidas'));
    });

    it('searches by a written-out date', function () {
        // Exercises the driver-aware date expression in SqlDate.
        Bill::factory()->create(['created_at' => '2026-08-07 12:00:00']);
        Bill::factory()->create(['created_at' => '2025-02-11 12:00:00']);

        $this->actingAs($this->user)
            ->get(route('bills', ['search' => '07 August 2026']))
            ->assertInertia(fn ($page) => $page->count('bills.data', 1));
    });
});

describe('creating', function () {
    it('creates a bill with its line items', function () {
        $this->actingAs($this->user)
            ->post(route('bills-create-post'), billPayload())
            ->assertRedirect(route('bills'))
            ->assertSessionHas('success');

        $bill = Bill::with('subBills')->sole();

        expect($bill->name)->toBe('INV-2026-001');
        expect($bill->client)->toBe('Nike');
        expect((float) $bill->total_amount)->toBe(1500.00);
        expect($bill->subBills)->toHaveCount(2);
        expect($bill->subBills->pluck('item')->all())->toBe(['Banner set', 'Revisions']);
    });

    it('uses the supplied bill date as the created_at', function () {
        $this->actingAs($this->user)
            ->post(route('bills-create-post'), billPayload(['bill_date' => '2026-01-15']));

        expect(Bill::sole()->created_at->toDateString())->toBe('2026-01-15');
    });

    it('requires a name, client, date, amount and at least one line item', function () {
        $this->actingAs($this->user)
            ->post(route('bills-create-post'), [])
            ->assertSessionHasErrors(['name', 'client', 'bill_date', 'total_amount', 'sub_bills']);

        expect(Bill::count())->toBe(0);
    });

    it('rejects a line item with a zero quantity', function () {
        $this->actingAs($this->user)
            ->post(route('bills-create-post'), billPayload([
                'sub_bills' => [['item' => 'Bad', 'quantity' => 0, 'unit_price' => 10, 'amount' => 0]],
            ]))
            ->assertSessionHasErrors('sub_bills.0.quantity');
    });

    it('rejects a non-numeric total', function () {
        $this->actingAs($this->user)
            ->post(route('bills-create-post'), billPayload(['total_amount' => 'free']))
            ->assertSessionHasErrors('total_amount');
    });

    it('stores attached documents against the bill and the uploader', function () {
        $this->actingAs($this->user)
            ->post(route('bills-create-post'), billPayload([
                'documents' => [UploadedFile::fake()->create('receipt.pdf', 32, 'application/pdf')],
            ]))
            ->assertRedirect(route('bills'));

        $document = BillDocument::sole();

        expect($document->filename)->toBe('receipt.pdf');
        expect($document->uploaded_by)->toBe($this->user->id);
        expect($document->bill_id)->toBe(Bill::sole()->id);
        Storage::disk('public')->assertExists($document->path);
    });

    it('rejects a document of a disallowed type', function () {
        $this->actingAs($this->user)
            ->post(route('bills-create-post'), billPayload([
                'documents' => [UploadedFile::fake()->create('script.exe', 8)],
            ]))
            ->assertSessionHasErrors('documents.0');

        expect(BillDocument::count())->toBe(0);
    });
});

describe('updating', function () {
    it('updates the bill and replaces its line items', function () {
        $bill = Bill::factory()->create(['name' => 'Draft']);
        $oldSub = SubBill::factory()->create(['bill_id' => $bill->id, 'item' => 'Old line']);

        $this->actingAs($this->user)
            ->put(route('bills-update', $bill->id), billPayload([
                'name' => 'INV-FINAL',
                'sub_bills' => [['item' => 'New line', 'quantity' => 2, 'unit_price' => 50, 'amount' => 100]],
            ]))
            ->assertRedirect(route('bills-edit', $bill->id))
            ->assertSessionHas('success');

        $bill->refresh()->load('subBills');

        expect($bill->name)->toBe('INV-FINAL');
        expect($bill->subBills)->toHaveCount(1);
        expect($bill->subBills->first()->item)->toBe('New line');
        expect(SubBill::find($oldSub->id))->toBeNull();
    });

    it('adds further documents without dropping the existing ones', function () {
        $bill = Bill::factory()->create();
        BillDocument::factory()->create(['bill_id' => $bill->id]);

        $this->actingAs($this->user)
            ->put(route('bills-update', $bill->id), billPayload([
                'documents' => [UploadedFile::fake()->create('extra.pdf', 16, 'application/pdf')],
            ]));

        expect($bill->load('documents')->documents)->toHaveCount(2);
    });

    it('still requires the mandatory fields on update', function () {
        $bill = Bill::factory()->create();

        $this->actingAs($this->user)
            ->put(route('bills-update', $bill->id), [])
            ->assertSessionHasErrors(['name', 'client', 'bill_date', 'total_amount', 'sub_bills']);
    });
});

describe('deleting', function () {
    it('deletes a bill with its line items and document files', function () {
        $bill = Bill::factory()->create();
        $sub = SubBill::factory()->create(['bill_id' => $bill->id]);
        $document = BillDocument::factory()->create(['bill_id' => $bill->id]);
        Storage::disk('public')->put($document->path, 'contents');

        $this->actingAs($this->user)
            ->delete(route('bills-delete', $bill->id))
            ->assertRedirect(route('bills'))
            ->assertSessionHas('success');

        expect(Bill::find($bill->id))->toBeNull();
        expect(SubBill::find($sub->id))->toBeNull();
        expect(BillDocument::find($document->id))->toBeNull();
        Storage::disk('public')->assertMissing($document->path);
    });
});

describe('documents', function () {
    it('deletes a single document and its file', function () {
        $bill = Bill::factory()->create();
        $document = BillDocument::factory()->create(['bill_id' => $bill->id]);
        Storage::disk('public')->put($document->path, 'contents');

        $this->actingAs($this->user)
            ->delete(route('bills-document-delete', [$bill->id, $document->id]))
            ->assertSessionHas('success');

        expect(BillDocument::find($document->id))->toBeNull();
        Storage::disk('public')->assertMissing($document->path);
    });

    it('refuses to delete a document belonging to another bill', function () {
        $bill = Bill::factory()->create();
        $otherDocument = BillDocument::factory()->create();

        $this->actingAs($this->user)
            ->delete(route('bills-document-delete', [$bill->id, $otherDocument->id]))
            ->assertStatus(404);

        expect(BillDocument::find($otherDocument->id))->not->toBeNull();
    });

    it('404s downloading a document whose file is gone', function () {
        $bill = Bill::factory()->create();
        $document = BillDocument::factory()->create(['bill_id' => $bill->id]);

        $this->actingAs($this->user)
            ->get(route('bills-document-download', [$bill->id, $document->id]))
            ->assertStatus(404);
    });
});
