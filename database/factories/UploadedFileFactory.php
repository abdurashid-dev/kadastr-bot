<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UploadedFile>
 */
class UploadedFileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->words(3, true),
            'original_filename' => fake()->word().'.'.fake()->fileExtension(),
            'telegram_file_id' => fake()->unique()->numerify('telegram_#####'),
            'file_path' => 'documents/'.fake()->word().'.pdf',
            'file_type' => fake()->randomElement(['document', 'image', 'video', 'audio']),
            'mime_type' => fake()->mimeType(),
            'file_size' => fake()->numberBetween(1024, 10485760), // 1KB to 10MB
            'status' => fake()->randomElement(['pending', 'waiting', 'accepted', 'rejected']),
            'admin_notes' => fake()->optional()->sentence(),
            'registered_count' => fake()->numberBetween(0, 100),
            'not_registered_count' => fake()->numberBetween(0, 100),
            'accepted_note' => fake()->optional()->sentence(),
        ];
    }

    /**
     * Indicate that the file is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
        ]);
    }

    /**
     * Indicate that the file is waiting.
     */
    public function waiting(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'waiting',
        ]);
    }

    /**
     * Indicate that the file is accepted.
     */
    public function accepted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'accepted',
        ]);
    }

    /**
     * Indicate that the file is rejected.
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'rejected',
            'admin_notes' => fake()->sentence(),
        ]);
    }
}
