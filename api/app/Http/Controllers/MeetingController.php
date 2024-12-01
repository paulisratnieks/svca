<?php

namespace App\Http\Controllers;

use App\Models\Meeting;
use Illuminate\Http\JsonResponse;

class MeetingController extends Controller
{
    public function store(): JsonResponse
    {
        return response()->json([
            'id' => Meeting::create(['user_id' => auth()->id()])->id,
        ]);
    }
}
