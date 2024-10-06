<?php

namespace App\Http\Controllers;

use App\Models\Meeting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MeetingController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        return response()->json([
            'id' => Meeting::create(['user_id' => $request->get('user_id')])->id,
        ]);
    }
}
