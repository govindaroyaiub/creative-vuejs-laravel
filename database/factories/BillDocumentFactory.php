<?php

namespace Database\Factories;

use App\Models\Bill;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BillDocument>
 */
class BillDocumentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $extensions = ['pdf', 'jpg', 'png', 'docx', 'xlsx'];
        $extension = $this->faker->randomElement($extensions);
        $filename = $this->faker->word() . '_' . time() . '.' . $extension;

        $mimeTypes = [
            'pdf' => 'application/pdf',
            'jpg' => 'image/jpeg',
            'png' => 'image/png',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ];

        return [
            'bill_id' => Bill::factory(),
            'filename' => $filename,
            'path' => 'bill_documents/' . $filename,
            'mime_type' => $mimeTypes[$extension],
            'file_size' => $this->faker->numberBetween(10240, 5242880), // Between 10KB and 5MB
            'uploaded_by' => User::factory(),
        ];
    }
}
