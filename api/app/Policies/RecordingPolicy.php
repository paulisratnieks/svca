<?php

namespace App\Policies;

use App\Models\Recording;
use App\Models\User;

class RecordingPolicy
{
    public function view(User $user, Recording $recording): bool
    {
        return $user->recordings->contains($recording)
            && $recording->active === false;
    }

    public function update(User $user, Recording $recording): bool
    {
        return $recording->user_id === $user->id
            && $recording->active === true;
    }

    public function delete(User $user, Recording $recording): bool
    {
        return $recording->user_id === $user->id
            && $recording->active === false;
    }
}
