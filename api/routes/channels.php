<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('meetings.{meetingId}', function (User $user, int $userId): bool {
    return true;
});
