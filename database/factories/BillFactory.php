<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Bill>
 */
class BillFactory extends Factory
{
    /**
     * `bills` is deliberately thin: name, client, total_amount. Line items
     * live in `sub_bills` and attachments in `bill_documents`; there is no
     * status, tax or due date on the bill itself.
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->words(3, true),
            // Free-text column, not a FK to `clients`.
            'client' => $this->faker->company(),
            'total_amount' => $this->faker->randomFloat(2, 100, 50000),
        ];
    }

    /** A bill whose total matches the sub-bill line items it will be given. */
    public function withoutTotal(): static
    {
        return $this->state(fn () => ['total_amount' => 0]);
    }
}
