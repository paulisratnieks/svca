<?php

namespace Database\Factories;

use App\Models\Recording;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Recording>
 */
class RecordingFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'active' => fake()->boolean(),
            'egress_id' => fake()->word(),
            'file_name' => fake()->filePath(),
            'user_id' => User::factory()->create()->id,
        ];
    }

    public function active(): static
    {
        return $this->state(fn(array $attributes) => [
            'active' => true,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn(array $attributes) => [
            'active' => false,
        ]);
    }
}
