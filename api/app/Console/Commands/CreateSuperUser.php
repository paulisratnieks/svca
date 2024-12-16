<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class CreateSuperUser extends Command
{
    /**
     * @var string
     */
    protected $signature = 'make:super-user';

    /**
     * @var string
     */
    protected $description = 'Create super user';

    public function handle(): void
    {
        $this->info('Creating super user');
        $isValidInput = false;
        $email = '';
        $password = '';

        while (!$isValidInput) {
            $email = $this->ask('Email');
            $password = $this->ask('Password');

            try {
                $this->validateInput($email, $password);
                $isValidInput = true;
            } catch (ValidationException $exception) {
                collect($exception->errors())->flatten()->each(fn (string $error) => $this->error($error));
                $this->info('Please enter valid information');
            }
        }

        User::factory()
            ->superUser()
            ->create([
                'email' => $email,
                'password' => Hash::make($password),
            ]);
    }

    /**
     * @throws ValidationException
     */
    private function validateInput(string $email, string $password): void
    {
        Validator::make([
            'name' => 'Super User',
            'email' => $email,
            'password' => $password,
        ], [
            'email' => ['required', 'email', 'unique:users'],
            'password' => ['required', 'string', 'min:10'],
        ])->validate();
    }
}
