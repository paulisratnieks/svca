<?php

namespace App\Http\Resources;

use App\Models\Recording;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Recording */
class RecordingResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'is_author' => $this->user_id === auth()->id(),
            'file_name' => $this->file_name,
            'created_at' => $this->created_at,
        ];
    }
}
