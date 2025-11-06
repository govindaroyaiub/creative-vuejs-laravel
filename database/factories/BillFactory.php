<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Bill>
 */
class BillFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subtotal = $this->faker->randomFloat(2, 100, 5000);
        $taxRate = $this->faker->randomFloat(2, 0, 25);
        $taxAmount = $subtotal * ($taxRate / 100);
        $totalAmount = $subtotal + $taxAmount;

        return [
            'bill_number' => 'INV-' . date('Y') . '-' . str_pad($this->faker->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'client_id' => Client::factory(),
            'user_id' => User::factory(),
            'bill_date' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'due_date' => function (array $attributes) {
                return $this->faker->dateTimeBetween($attributes['bill_date'], '+2 months');
            },
            'subtotal' => $subtotal,
            'tax_rate' => $taxRate,
            'tax_amount' => $taxAmount,
            'total_amount' => $totalAmount,
            'status' => $this->faker->randomElement(['pending', 'paid', 'overdue', 'cancelled']),
            'notes' => $this->faker->optional()->paragraph(),
            'paid_date' => function (array $attributes) {
                return $attributes['status'] === 'paid'
                    ? $this->faker->dateTimeBetween($attributes['bill_date'], 'now')
                    : null;
            },
            'created_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'updated_at' => function (array $attributes) {
                return $this->faker->dateTimeBetween($attributes['created_at'], 'now');
            },
        ];
    }

    /**
     * Indicate that the bill is paid.
     */
    public function paid(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'paid',
            'paid_date' => $this->faker->dateTimeBetween($attributes['bill_date'] ?? '-1 month', 'now'),
        ]);
    }

    /**
     * Indicate that the bill is pending.
     */
    public function pending(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'pending',
            'paid_date' => null,
        ]);
    }

    /**
     * Indicate that the bill is overdue.
     */
    public function overdue(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'overdue',
            'due_date' => $this->faker->dateTimeBetween('-2 months', '-1 day'),
            'paid_date' => null,
        ]);
    }

    /**
     * Create a bill for a specific user.
     */
    public function forUser(User $user): static
    {
        return $this->state(fn(array $attributes) => [
            'user_id' => $user->id,
        ]);
    }

    /**
     * Create a bill for a specific client.
     */
    public function forClient(Client $client): static
    {
        return $this->state(fn(array $attributes) => [
            'client_id' => $client->id,
        ]);
    }

    /**
     * Create a high-value bill.
     */
    public function highValue(): static
    {
        $subtotal = $this->faker->randomFloat(2, 5000, 20000);
        $taxRate = $this->faker->randomFloat(2, 10, 25);
        $taxAmount = $subtotal * ($taxRate / 100);

        return $this->state(fn(array $attributes) => [
            'subtotal' => $subtotal,
            'tax_rate' => $taxRate,
            'tax_amount' => $taxAmount,
            'total_amount' => $subtotal + $taxAmount,
        ]);
    }

    /**
     * Create a recent bill.
     */
    public function recent(): static
    {
        return $this->state(fn(array $attributes) => [
            'bill_date' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'due_date' => $this->faker->dateTimeBetween('now', '+1 month'),
        ]);
    }
}
