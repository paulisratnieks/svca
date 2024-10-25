<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MediaTokenRequest extends FormRequest
{
    /**
     * @return array<string, array<string>>
     */
    public function rules(): array
    {
        return [
            'room_name' => ['required', 'uuid'],
        ];
    }
}
