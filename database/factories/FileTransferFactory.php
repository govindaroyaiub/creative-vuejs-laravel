<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\FileTransfer>
 */
class FileTransferFactory extends Factory
{
    public function definition(): array
    {
        $name = $this->faker->words(3, true);

        return [
            // The public viewer URL is keyed on this UUID, so it carries the
            // access entropy — it is not a readable slug.
            'slug' => Str::uuid()->toString(),
            'name' => Str::title($name),
            // `client` is a free-text column on this table, not a FK.
            'client' => $this->faker->company(),
            'preview_id' => null,
            'user_id' => User::factory(),
            // Comma-joined list of paths relative to public/.
            'file_path' => 'Transfer Files/' . Str::slug($name) . '_' . $this->faker->unixTime() . '_' . Str::random(13) . '.zip',
        ];
    }

    /** A transfer holding more than one archive. */
    public function withFiles(int $count): static
    {
        return $this->state(fn () => [
            'file_path' => collect(range(1, $count))
                ->map(fn ($i) => 'Transfer Files/asset_' . $i . '_' . Str::random(13) . '.zip')
                ->implode(','),
        ]);
    }

    /** A transfer created by approving a revision round on a preview. */
    public function forPreview(int $previewId): static
    {
        return $this->state(fn () => ['preview_id' => $previewId]);
    }

    public function forUser(User $user): static
    {
        return $this->state(fn () => ['user_id' => $user->id]);
    }
}
