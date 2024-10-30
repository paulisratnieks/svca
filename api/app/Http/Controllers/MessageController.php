<?php

namespace App\Http\Controllers;

use App\Events\MessageCreated;
use App\Models\Message;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function store(Request $request, string $meetingId): JsonResponse
    {
        Message::create([
            'user_id' => $request->get('user_id'),
            'meeting_id' => $meetingId,
            'body' => $request->get('body'),
        ]);

        return response()->json(status: 201);
    }
}
