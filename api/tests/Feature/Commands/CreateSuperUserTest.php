<?php

namespace Tests\Feature\Commands;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CreateSuperUserTest extends TestCase
{
    use RefreshDatabase;

    public function test_when_create_super_user_command_called_with_valid_input_then_see_super_user_created(): void
    {
        $email = fake()->email();
        $password = fake()->password(10);

        $this->artisan('make:super-user')
            ->expectsOutput('Creating super user')
            ->expectsQuestion('Email', $email)
            ->expectsQuestion('Password', $password)
            ->expectsOutput('Super has been created')
            ->assertExitCode(0);
        $this->assertEquals(
            User::firstOrFail()->only('name', 'email', 'super_user'),
            [
                'name' => 'Super User',
                'email' => $email,
                'super_user' => true,
            ]
        );
        $this->assertTrue(Hash::check($password, User::firstOrFail()->password));
    }

    public function test_when_create_super_user_command_called_with_invalid_input_then_see_reenter_prompt_and_after_valid_input_super_user_created(): void
    {
        $email = fake()->email();
        $password = fake()->password(10);

        $this->artisan('make:super-user')
            ->expectsOutput('Creating super user')
            ->expectsQuestion('Email', fake()->word())
            ->expectsQuestion('Password', fake()->password(9, 9))
            ->expectsOutput('The email field must be a valid email address.')
            ->expectsOutput('The password field must be at least 10 characters.')
            ->expectsOutput('Please enter valid information')
            ->expectsQuestion('Email', $email)
            ->expectsQuestion('Password', $password)
            ->expectsOutput('Super has been created')
            ->assertExitCode(0);
        $this->assertEquals(
            User::firstOrFail()->only('name', 'email', 'super_user'),
            [
                'name' => 'Super User',
                'email' => $email,
                'super_user' => true,
            ]
        );
        $this->assertTrue(Hash::check($password, User::firstOrFail()->password));
    }
}
