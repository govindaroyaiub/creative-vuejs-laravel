<?php

namespace Database\Factories;

use App\Models\Designation;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            // Must be present even as null. `User::designation()` is a
            // belongsTo whose relation name matches its foreign key column,
            // so eager-loading it reads `$user->designation` — and that
            // resolves to the relation instead of the column when the
            // attribute key is missing, which throws. A DB-fetched user
            // always has the key; a factory-built one only has what is
            // listed here.
            'designation' => null,
        ];
    }

    /** Give the user a designation record to belong to. */
    public function withDesignation(?Designation $designation = null): static
    {
        return $this->state(fn () => [
            'designation' => ($designation ?? Designation::factory()->create())->id,
        ]);
    }

    /** Full access, for tests that hit routes behind CheckUserPermission. */
    public function admin(): static
    {
        return $this->state(fn () => [
            'role' => 'admin',
            'permissions' => ['*'],
        ]);
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
