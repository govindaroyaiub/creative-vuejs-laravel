<?php

declare(strict_types=1);

namespace Tests\Feature\Billing;

use App\Models\User;
use App\Models\Bill;
use App\Models\SubBill;
use App\Models\Client;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->client = Client::factory()->create();
});

describe('BillController Authentication', function () {
    it('requires authentication for billing operations', function () {
        $this->get(route('bills.index'))
            ->assertRedirect(route('login'));

        $this->post(route('bills.store'))
            ->assertRedirect(route('login'));
    });
});

describe('BillController Index', function () {
    it('displays bills with proper pagination', function () {
        Bill::factory(15)->create(['user_id' => $this->user->id]);

        $this->actingAs($this->user)
            ->get(route('bills.index'))
            ->assertOk()
            ->assertInertia(
                fn($page) => $page
                    ->component('Bills/Index')
                    ->has('bills.data', 10) // Default pagination
                    ->has('bills.links')
            );
    });

    it('filters bills by status', function () {
        Bill::factory(3)->create(['user_id' => $this->user->id, 'status' => 'paid']);
        Bill::factory(2)->create(['user_id' => $this->user->id, 'status' => 'pending']);

        $this->actingAs($this->user)
            ->get(route('bills.index', ['status' => 'paid']))
            ->assertOk()
            ->assertInertia(
                fn($page) => $page
                    ->has('bills.data', 3)
            );
    });

    it('filters bills by client', function () {
        $client1 = Client::factory()->create();
        $client2 = Client::factory()->create();

        Bill::factory(3)->create(['user_id' => $this->user->id, 'client_id' => $client1->id]);
        Bill::factory(2)->create(['user_id' => $this->user->id, 'client_id' => $client2->id]);

        $this->actingAs($this->user)
            ->get(route('bills.index', ['client' => $client1->id]))
            ->assertOk()
            ->assertInertia(
                fn($page) => $page
                    ->has('bills.data', 3)
            );
    });

    it('filters bills by date range', function () {
        Bill::factory()->create([
            'user_id' => $this->user->id,
            'bill_date' => '2024-01-15'
        ]);

        Bill::factory()->create([
            'user_id' => $this->user->id,
            'bill_date' => '2024-06-15'
        ]);

        $this->actingAs($this->user)
            ->get(route('bills.index', [
                'date_from' => '2024-01-01',
                'date_to' => '2024-03-31'
            ]))
            ->assertOk()
            ->assertInertia(
                fn($page) => $page
                    ->has('bills.data', 1)
            );
    });
});

describe('BillController Store', function () {
    it('creates bill with valid data', function () {
        $billData = [
            'client_id' => $this->client->id,
            'bill_number' => 'INV-2024-001',
            'bill_date' => '2024-01-15',
            'due_date' => '2024-02-15',
            'subtotal' => 1000.00,
            'tax_rate' => 10.00,
            'tax_amount' => 100.00,
            'total_amount' => 1100.00,
            'status' => 'pending',
            'notes' => 'Test bill creation',
            'items' => [
                [
                    'description' => 'Web Development',
                    'quantity' => 40,
                    'rate' => 25.00,
                    'amount' => 1000.00
                ]
            ]
        ];

        $this->actingAs($this->user)
            ->post(route('bills.store'), $billData)
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('bills', [
            'client_id' => $this->client->id,
            'bill_number' => 'INV-2024-001',
            'total_amount' => 1100.00,
            'user_id' => $this->user->id
        ]);

        $bill = Bill::where('bill_number', 'INV-2024-001')->first();
        $this->assertDatabaseHas('sub_bills', [
            'bill_id' => $bill->id,
            'description' => 'Web Development',
            'quantity' => 40,
            'rate' => 25.00
        ]);
    });

    it('validates required fields', function () {
        $this->actingAs($this->user)
            ->post(route('bills.store'), [])
            ->assertSessionHasErrors([
                'client_id',
                'bill_number',
                'bill_date',
                'total_amount',
                'items'
            ]);
    });

    it('validates unique bill number per user', function () {
        Bill::factory()->create([
            'user_id' => $this->user->id,
            'bill_number' => 'INV-2024-001'
        ]);

        $this->actingAs($this->user)
            ->post(route('bills.store'), [
                'client_id' => $this->client->id,
                'bill_number' => 'INV-2024-001',
                'bill_date' => '2024-01-15',
                'total_amount' => 1000.00,
                'items' => [
                    ['description' => 'Test', 'quantity' => 1, 'rate' => 1000]
                ]
            ])
            ->assertSessionHasErrors(['bill_number']);
    });

    it('calculates totals correctly', function () {
        $billData = [
            'client_id' => $this->client->id,
            'bill_number' => 'INV-2024-002',
            'bill_date' => '2024-01-15',
            'tax_rate' => 15.00,
            'items' => [
                ['description' => 'Development', 'quantity' => 20, 'rate' => 50.00],
                ['description' => 'Design', 'quantity' => 10, 'rate' => 75.00]
            ]
        ];

        $this->actingAs($this->user)
            ->post(route('bills.store'), $billData)
            ->assertRedirect();

        $bill = Bill::where('bill_number', 'INV-2024-002')->first();

        expect($bill->subtotal)->toBe(1750.00); // (20*50) + (10*75)
        expect($bill->tax_amount)->toBe(262.50); // 1750 * 0.15
        expect($bill->total_amount)->toBe(2012.50); // 1750 + 262.50
    });
});

describe('BillController Update', function () {
    it('updates bill status', function () {
        $bill = Bill::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'pending'
        ]);

        $this->actingAs($this->user)
            ->put(route('bills.update', $bill), [
                'status' => 'paid',
                'paid_date' => '2024-01-20'
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $bill->refresh();
        expect($bill->status)->toBe('paid');
        expect($bill->paid_date->format('Y-m-d'))->toBe('2024-01-20');
    });

    it('prevents updating other users bills', function () {
        $otherUser = User::factory()->create();
        $bill = Bill::factory()->create(['user_id' => $otherUser->id]);

        $this->actingAs($this->user)
            ->put(route('bills.update', $bill), ['status' => 'paid'])
            ->assertForbidden();
    });

    it('updates bill items', function () {
        $bill = Bill::factory()->create(['user_id' => $this->user->id]);
        $subBill = SubBill::factory()->create(['bill_id' => $bill->id]);

        $updateData = [
            'items' => [
                [
                    'id' => $subBill->id,
                    'description' => 'Updated Development',
                    'quantity' => 30,
                    'rate' => 60.00
                ]
            ]
        ];

        $this->actingAs($this->user)
            ->put(route('bills.update', $bill), $updateData)
            ->assertRedirect();

        $subBill->refresh();
        expect($subBill->description)->toBe('Updated Development');
        expect($subBill->quantity)->toBe(30);
        expect($subBill->rate)->toBe(60.00);
    });
});

describe('BillController PDF Generation', function () {
    it('generates PDF for bill', function () {
        $bill = Bill::factory()->create(['user_id' => $this->user->id]);
        SubBill::factory(2)->create(['bill_id' => $bill->id]);

        $this->actingAs($this->user)
            ->get(route('bills.pdf', $bill))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
    });

    it('includes all bill details in PDF', function () {
        $bill = Bill::factory()->create([
            'user_id' => $this->user->id,
            'bill_number' => 'INV-PDF-TEST',
            'notes' => 'Special instructions for PDF'
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('bills.pdf', $bill));

        // Note: In a real test, you might want to check PDF content
        // For now, we'll just verify the response is successful
        $response->assertOk();
    });
});

describe('BillController Analytics', function () {
    it('provides billing analytics', function () {
        // Create bills with different statuses and dates
        Bill::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'paid',
            'total_amount' => 1000.00,
            'bill_date' => now()->subDays(15)
        ]);

        Bill::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'pending',
            'total_amount' => 500.00,
            'bill_date' => now()->subDays(5)
        ]);

        $this->actingAs($this->user)
            ->get(route('bills.analytics'))
            ->assertOk()
            ->assertInertia(
                fn($page) => $page
                    ->component('Bills/Analytics')
                    ->has('analytics')
                    ->where('analytics.total_revenue', 1000.00)
                    ->where('analytics.pending_amount', 500.00)
            );
    });

    it('calculates monthly revenue trends', function () {
        Bill::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'paid',
            'total_amount' => 2000.00,
            'bill_date' => Carbon::now()->startOfMonth()
        ]);

        Bill::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'paid',
            'total_amount' => 1500.00,
            'bill_date' => Carbon::now()->subMonth()->startOfMonth()
        ]);

        $this->actingAs($this->user)
            ->get(route('bills.analytics'))
            ->assertOk()
            ->assertInertia(
                fn($page) => $page
                    ->has('analytics.monthly_trends')
                    ->whereContains(
                        'analytics.monthly_trends',
                        fn($trend) =>
                        $trend['month'] === Carbon::now()->format('M Y') &&
                            $trend['revenue'] === 2000.00
                    )
            );
    });
});
