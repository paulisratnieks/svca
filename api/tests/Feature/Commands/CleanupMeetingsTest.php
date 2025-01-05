<?php

namespace Tests\Feature\Commands;

use Agence104\LiveKit\RoomServiceClient;
use App\Models\Meeting;
use App\Models\Recording;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livekit\ListRoomsResponse;
use Livekit\Room;
use Mockery\MockInterface;
use Tests\TestCase;

class CleanupMeetingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_when_cleanup_called_then_inactive_meetings_get_soft_deleted_and_active_recordings_get_stopped(): void
    {
        User::factory()->create();
        [$activeMeeting, $activeMeetingRecording] = $this->meetingWithActiveRecording();
        [$inactiveMeeting, $inactiveMeetingRecording] = $this->meetingWithActiveRecording();
        $this->partialMock(RoomServiceClient::class, function (MockInterface $mock) use ($activeMeeting) {
            $mock->shouldReceive('listRooms')
                ->once()
                ->andReturn((new ListRoomsResponse())->setRooms([(new Room())->setName($activeMeeting->id)]));
        });

        $this->artisan('app:cleanup-meetings')
            ->assertExitCode(0);
        $this->assertSoftDeleted($inactiveMeeting);
        $this->assertModelExists($activeMeeting);
        $this->assertEquals(
            $activeMeetingRecording->toArray(),
            $activeMeeting->recordings()->first()?->toArray()
        );
        $this->assertEquals(
            [
                ...$inactiveMeetingRecording->only('id', 'user_id', 'egress_id', 'active', 'file_name'),
                'active' => false,
            ],
            $inactiveMeeting->recordings()->first()?->only('id', 'user_id', 'egress_id', 'active', 'file_name')
        );
    }

    /**
     * @return array{0: Meeting, 1: Recording}
     */
    private function meetingWithActiveRecording(): array
    {
        $meeting = Meeting::factory()->create();
        $recording = Recording::factory()
            ->active()
            ->for($meeting)
            ->create();

        return [$meeting, $recording];
    }
}
