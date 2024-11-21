<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    /**
     * @return array<string, array<string>>
     */
    public function rules(): array
    {
        return [
            'password' => ['required', 'string', 'min:10'],
            'email' => ['required', 'email', 'unique:users'],
            'name' => ['required', 'min:5', 'unique:users'],
        ];
    }
}
