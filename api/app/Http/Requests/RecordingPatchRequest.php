<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RecordingPatchRequest extends FormRequest
{
    /**
     * @return array<string, string[]>
     */
    public function rules(): array
    {
        return [
            'id' => ['required', 'numeric'],
        ];
    }
}
