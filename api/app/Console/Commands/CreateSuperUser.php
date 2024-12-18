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

    public function handle(): int
    {
        $this->info('Creating super user');
        $isValidInput = false;
        $email = '';
        $password = '';

        while (!$isValidInput) {
            $email = stringify($this->ask('Email'));
            $password = stringify($this->ask('Password'));

            try {
                $this->validateInput($email, $password);
                $isValidInput = true;
            } catch (ValidationException $exception) {
                collect($exception->errors())->flatten()->each(fn(mixed $error) => $this->error(stringify($error)));
                $this->info('Please enter valid information');
            }
        }

        User::factory()
            ->superUser()
            ->create([
                'name' => 'Super User',
                'email' => $email,
                'password' => Hash::make($password),
            ]);

        $this->info('Super has been created');

        return 0;
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
