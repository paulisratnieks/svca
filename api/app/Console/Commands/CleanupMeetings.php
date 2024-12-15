<?php

namespace App\Console\Commands;

use Agence104\LiveKit\RoomServiceClient;
use App\Models\Meeting;
use Illuminate\Console\Command;
use Livekit\Room;

class CleanupMeetings extends Command
{
    /**
     * @var string
     */
    protected $signature = 'app:cleanup-meetings';

    /**
     * @var string
     */
    protected $description = 'Cleanup stale meeting and recording models';

    public function __construct(
        private readonly RoomServiceClient $client
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $meetingIds = Meeting::pluck('id');
        /**
         * @var iterable<int, Room> $meetings
         */
        $meetings = $this->client->listRooms()->getRooms()->getIterator();
        $activeMeetingIds = collect($meetings)
            ->map(fn(Room $room): string => $room->getName());

        $inactiveMeetingIds = $meetingIds->diff($activeMeetingIds);
        $inactiveMeetings = Meeting::whereIn('id', $inactiveMeetingIds)->get();
        $inactiveMeetings->each(function (Meeting $meeting): void {
            $meeting->recordings()->update(['active' => false]);
        });
        Meeting::whereIn('id', $inactiveMeetingIds)->delete();

        return 0;
    }
}
