<?php

namespace App\Http\Controllers;

use App\Models\Meeting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class MeetingController extends Controller
{
    public function store(): JsonResponse
    {
        return response()->json([
            'id' => Meeting::create(['user_id' => auth()->id()])->id,
        ], \Symfony\Component\HttpFoundation\Response::HTTP_CREATED);
    }

    public function show(Meeting $meeting): Response
    {
        return response()->noContent();
    }
}
