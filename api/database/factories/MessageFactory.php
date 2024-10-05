<?php

namespace Database\Factories;

use App\Models\Meeting;
use App\Models\Message;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Message>
 */
class MessageFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::first()->id,
            'meeting_id' => Meeting::first()->id,
            'body' => fake()->text(),
        ];
    }
}
