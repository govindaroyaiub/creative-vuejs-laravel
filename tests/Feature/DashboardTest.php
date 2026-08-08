<?php

use App\Models\Bill;
use App\Models\Client;
use App\Models\User;
use App\Models\newPreview;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/** The dashboard proper is only rendered for Planet Nine staff. */
function planetNineUser(): User
{
    $client = Client::factory()->create(['name' => 'Planet Nine']);

    return User::factory()->admin()->create(['client_id' => $client->id]);
}

test('guests are redirected to the login page', function () {
    $response = $this->get('/dashboard');
    $response->assertRedirect('/login');
});

test('authenticated users can visit the dashboard', function () {
    $this->actingAs(planetNineUser());

    $this->get('/dashboard')
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page->component('Dashboard'));
});

test('authenticated users without permissions are refused', function () {
    $this->actingAs(User::factory()->create());

    $this->get('/dashboard')->assertStatus(403);
});

test('a user outside Planet Nine gets the guest welcome instead', function () {
    $client = Client::factory()->create(['name' => 'Some Other Agency']);
    $this->actingAs(User::factory()->admin()->create(['client_id' => $client->id]));

    $this->get('/dashboard')
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page->component('GuestWelcome'));
});

test('a user with no client at all does not crash the dashboard', function () {
    // client_id is nullable and deleting a client nulls it, so this is reachable.
    $this->actingAs(User::factory()->admin()->create(['client_id' => null]));

    $this->get('/dashboard')
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page->component('GuestWelcome'));
});

test('the dashboard buckets this year of activity by month', function () {
    $this->actingAs(planetNineUser());

    Bill::factory()->create(['created_at' => now()->startOfYear(), 'total_amount' => 1500.50]);
    Bill::factory()->create(['created_at' => now()->startOfYear(), 'total_amount' => 500.25]);
    newPreview::factory(2)->create(['created_at' => now()->startOfYear()]);

    $january = (int) now()->startOfYear()->format('n');

    $this->get('/dashboard')
        ->assertStatus(200)
        ->assertInertia(function ($page) use ($january) {
            $page->where("monthlyBillTotals.{$january}", 2000.75)
                ->where("monthlyPreviewStats.{$january}", 2)
                ->where('totalBill', 2000.75)
                ->where('previewCount', 2);
        });
});
