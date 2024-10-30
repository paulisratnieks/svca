<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_log_in_if_correct_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'test@test.com',
            'password' => Hash::make('password'),
        ]);

        $this->postJson('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertStatus(200);
    }

    public function test_user_can_not_log_in_if_incorrect_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'test@test.com',
        ]);

        $this->postJson('/login', [
            'email' => $user->email,
            'password' => 'incorrect_password',
        ])->assertStatus(401);
    }

    public function test_user_can_register_with_valid_credentials(): void
    {
        $email = 'test@example.com';

        $this->postJson('/register', [
            'email' => $email,
            'name' => 'John Doe',
            'password' => 'password123',
        ])->assertCreated();
        $this->assertModelExists(User::where('email', $email)->first());
    }

    public function test_user_cannot_register_with_invalid_credentials(): void
    {
        $userFields = [
            'name' => 'John Doe',
            'email' => 'test@example.com',
        ];
        User::factory()->create([
            ...$userFields
        ]);

        $this->postJson('/register', [
            ...$userFields,
            'password' => 'password',
        ])->assertInvalid([
            'name' => 'The name has already been taken.',
            'email' => 'The email has already been taken.',
            'password' => 'The password field must be at least 10 characters.',
        ]);
    }
}
