<?php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('meetings.{meetingId}', function (User $user, string $meetingId): array {
    return $user->only('id', 'name');
});
