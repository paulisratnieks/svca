<?php

namespace App\Http\Controllers;

use App\Models\Meeting;
use Illuminate\Foundation\Application;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\ResponseFactory;

class MeetingController extends Controller
{
    public function show(Meeting $meeting): ResponseFactory|Application|Response
    {
        return response()->noContent();
    }

    public function store(Request $request): JsonResponse
    {
        return response()->json([
            'id' => Meeting::create(['user_id' => auth()->id()])->id,
        ]);
    }
}
