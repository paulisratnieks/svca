<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RecordingDestroyRequest extends FormRequest
{
    /**
     * @return array<string, string[]>
     */
    public function rules(): array
    {
        return [
            'ids' => ['array', 'required'],
            'ids.*' => ['exists:recordings,id'],
        ];
    }
}
