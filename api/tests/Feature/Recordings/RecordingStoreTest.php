<?php

namespace Tests\Feature\Recordings;

use Agence104\LiveKit\EgressServiceClient;
use App\Models\Meeting;
use App\Models\Recording;
use App\Models\User;
use Database\Seeders\UserWithInactiveRecordingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livekit\EgressInfo;
use Livekit\FileInfo;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class RecordingStoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_when_user_starts_recoding_then_egress_request_sent_and_model_created(): void
    {
        $roomName = fake()->uuid();
        $egressId = fake()->word();
        $user = User::factory()->create();
        Meeting::factory()->create(['id' => $roomName]);
        $this->partialMock(EgressServiceClient::class, function (MockInterface $mock) use ($roomName, $egressId) {
            $mock->shouldReceive('startRoomCompositeEgress')
                ->once()
                ->withArgs(function (string $argumentRoomName) use ($roomName) {
                    return $argumentRoomName === $roomName;
                })
                ->andReturn(
                    (new EgressInfo())
                        ->setEgressId($egressId)
                        ->setFileResults([(new FileInfo())->setFilename($roomName . '.mp4')])
                        ->setRoomName($roomName)
                );
        });

        $response = $this
            ->actingAs($user)
            ->postJson('recordings', [
                'room_name' => $roomName,
            ]);
        $recording = Recording::firstOrFail();
        $response
            ->assertCreated()
            ->assertJson(['id' => $recording->id]);
        $this->assertEquals(
            $recording->only('user_id', 'file_name', 'active', 'egress_id', 'updated_at'),
            [
                'user_id' => $user->id,
                'file_name' => $roomName . '.mp4',
                'active' => 1,
                'egress_id' => $egressId,
                'updated_at' => $recording->created_at,
            ]
        );
    }
}
