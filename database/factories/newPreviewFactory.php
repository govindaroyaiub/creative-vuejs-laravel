<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\newPreview>
 */
class newPreviewFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph(),
            'file_path' => 'previews/' . $this->faker->uuid . '.mp4',
            'file_size' => $this->faker->numberBetween(1048576, 104857600), // 1MB to 100MB
            'duration' => $this->faker->time('H:i:s'),
            'resolution' => $this->faker->randomElement(['1920x1080', '1280x720', '3840x2160']),
            'fps' => $this->faker->randomElement([24, 30, 60]),
            'status' => $this->faker->randomElement(['active', 'inactive', 'processing']),
            'user_id' => User::factory(),
            'client_id' => Client::factory(),
            'created_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'updated_at' => function (array $attributes) {
                return $this->faker->dateTimeBetween($attributes['created_at'], 'now');
            },
        ];
    }

    /**
     * Indicate that the preview is active.
     */
    public function active(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'active',
        ]);
    }

    /**
     * Indicate that the preview is processing.
     */
    public function processing(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'processing',
        ]);
    }

    /**
     * Create a high-resolution preview.
     */
    public function highResolution(): static
    {
        return $this->state(fn(array $attributes) => [
            'resolution' => '3840x2160',
            'fps' => 60,
            'file_size' => $this->faker->numberBetween(104857600, 524288000), // 100MB to 500MB
        ]);
    }

    /**
     * Create a preview with specific user.
     */
    public function forUser(User $user): static
    {
        return $this->state(fn(array $attributes) => [
            'user_id' => $user->id,
        ]);
    }

    /**
     * Create a preview with specific client.
     */
    public function forClient(Client $client): static
    {
        return $this->state(fn(array $attributes) => [
            'client_id' => $client->id,
        ]);
    }
}
