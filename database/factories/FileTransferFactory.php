<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\FileTransfer>
 */
class FileTransferFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $fileName = $this->faker->word . '.' . $this->faker->randomElement(['pdf', 'docx', 'xlsx', 'jpg', 'png', 'mp4', 'zip']);

        return [
            'original_name' => $fileName,
            'file_path' => 'transfers/' . Str::uuid() . '_' . $fileName,
            'file_size' => $this->faker->numberBetween(1024, 10485760), // 1KB to 10MB
            'mime_type' => $this->faker->randomElement([
                'application/pdf',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'image/jpeg',
                'image/png',
                'video/mp4',
                'application/zip'
            ]),
            'download_token' => Str::random(32),
            'user_id' => User::factory(),
            'client_id' => Client::factory(),
            'description' => $this->faker->optional()->sentence(),
            'password_protected' => $this->faker->boolean(30), // 30% chance
            'password' => function (array $attributes) {
                return $attributes['password_protected']
                    ? bcrypt($this->faker->password(8, 12))
                    : null;
            },
            'expires_at' => $this->faker->optional(80)->dateTimeBetween('now', '+6 months'), // 80% have expiry
            'max_downloads' => $this->faker->optional(60)->numberBetween(1, 10), // 60% have download limit
            'download_count' => 0,
            'last_downloaded_at' => null,
            'created_at' => $this->faker->dateTimeBetween('-6 months', 'now'),
            'updated_at' => function (array $attributes) {
                return $this->faker->dateTimeBetween($attributes['created_at'], 'now');
            },
        ];
    }

    /**
     * Create a password protected file transfer.
     */
    public function passwordProtected(): static
    {
        return $this->state(fn(array $attributes) => [
            'password_protected' => true,
            'password' => bcrypt('secret123'),
        ]);
    }

    /**
     * Create an expired file transfer.
     */
    public function expired(): static
    {
        return $this->state(fn(array $attributes) => [
            'expires_at' => $this->faker->dateTimeBetween('-2 months', '-1 day'),
        ]);
    }

    /**
     * Create a file transfer that has been downloaded.
     */
    public function downloaded(int $times = 1): static
    {
        return $this->state(fn(array $attributes) => [
            'download_count' => $times,
            'last_downloaded_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ]);
    }

    /**
     * Create a file transfer with download limit reached.
     */
    public function limitReached(): static
    {
        $maxDownloads = $this->faker->numberBetween(3, 10);

        return $this->state(fn(array $attributes) => [
            'max_downloads' => $maxDownloads,
            'download_count' => $maxDownloads,
            'last_downloaded_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
        ]);
    }

    /**
     * Create a file transfer for a specific user.
     */
    public function forUser(User $user): static
    {
        return $this->state(fn(array $attributes) => [
            'user_id' => $user->id,
        ]);
    }

    /**
     * Create a file transfer for a specific client.
     */
    public function forClient(Client $client): static
    {
        return $this->state(fn(array $attributes) => [
            'client_id' => $client->id,
        ]);
    }

    /**
     * Create a large file transfer.
     */
    public function largeFile(): static
    {
        return $this->state(fn(array $attributes) => [
            'file_size' => $this->faker->numberBetween(52428800, 104857600), // 50MB to 100MB
            'original_name' => 'large_video_' . $this->faker->randomNumber(4) . '.mp4',
            'mime_type' => 'video/mp4',
        ]);
    }
}
