<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'super_user' => false,
            'password' => static::$password ??= Hash::make('password'),
        ];
    }

    public function superUser(): static
    {
        return $this->state(fn(array $attributes) => [
            'super_user' => true,
        ]);
    }
}
