<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ColorPalette>
 */
class ColorPaletteFactory extends Factory
{
    public function definition(): array
    {
        // Every colour column is NOT NULL, so all seven have to be filled.
        return [
            'name' => $this->faker->unique()->words(2, true),
            'primary' => $this->faker->hexColor(),
            'secondary' => $this->faker->hexColor(),
            'tertiary' => $this->faker->hexColor(),
            'quaternary' => $this->faker->hexColor(),
            'quinary' => $this->faker->hexColor(),
            'senary' => $this->faker->hexColor(),
            'septenary' => $this->faker->hexColor(),
            'feedbackTab_inactive_image' => 'uploads/colorPalette/' . $this->faker->uuid() . '.png',
            'feedbackTab_active_image' => 'uploads/colorPalette/' . $this->faker->uuid() . '.png',
            'rightSideTab_feedback_description_image' => 'uploads/colorPalette/' . $this->faker->uuid() . '.png',
            'rightSideTab_color_palette_image' => 'uploads/colorPalette/' . $this->faker->uuid() . '.png',
            'header_image' => 'uploads/colorPalette/' . $this->faker->uuid() . '.png',
            'status' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['status' => false]);
    }
}
