<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageCreated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(private readonly Message $message)
    {
    }

    /**
     * @return array<string, string|int>
     */
    public function broadcastWith(): array
    {
        return $this->message->only('created_at', 'body', 'user_id');
    }


    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('meetings.' . $this->message->meeting_id);
    }
}
