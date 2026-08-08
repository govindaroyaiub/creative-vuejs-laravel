<?php

namespace Database\Factories;

use App\Models\ColorPalette;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Client>
 */
class ClientFactory extends Factory
{
    public function definition(): array
    {
        $name = $this->faker->unique()->company();

        return [
            'name' => $name,
            'website' => 'https://' . Str::slug($name) . '.com',
            'preview_url' => null,
            // `logo` is NOT NULL in the schema.
            'logo' => 'uploads/clients/' . $this->faker->uuid() . '.png',
            // `color_palette_id` is a non-nullable constrained FK.
            'color_palette_id' => ColorPalette::factory(),
        ];
    }
}
