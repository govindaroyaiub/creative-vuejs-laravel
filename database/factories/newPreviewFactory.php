<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\ColorPalette;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\newPreview>
 */
class newPreviewFactory extends Factory
{
    public function definition(): array
    {
        return [
            'slug' => Str::uuid()->toString(),
            'name' => Str::title($this->faker->words(3, true)),
            'client_id' => Client::factory(),
            // Despite the name this holds a `clients.id` — the controller
            // validates it with `exists:clients,id` and renders that client's
            // logo in the preview header.
            'header_logo_id' => Client::factory(),
            // Cast to array on the model; holds user ids.
            'team_members' => [],
            'uploader_id' => User::factory(),
            'color_palette_id' => ColorPalette::factory(),
            'requires_login' => false,
            'show_planetnine_logo' => true,
            'show_sidebar_logo' => true,
            'show_footer' => true,
        ];
    }

    public function requiringLogin(): static
    {
        return $this->state(fn () => ['requires_login' => true]);
    }

    /** Reuse one client for both the owner and the header logo. */
    public function forClient(Client $client): static
    {
        return $this->state(fn () => [
            'client_id' => $client->id,
            'header_logo_id' => $client->id,
        ]);
    }

    public function uploadedBy(User $user): static
    {
        return $this->state(fn () => ['uploader_id' => $user->id]);
    }

    public function withTeam(array $userIds): static
    {
        return $this->state(fn () => ['team_members' => $userIds]);
    }
}
