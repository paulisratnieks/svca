<?php

namespace Tests\Feature;

use Agence104\LiveKit\EgressServiceClient;
use App\Models\Recording;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livekit\EgressInfo;
use Livekit\FileInfo;
use Mockery\MockInterface;
use Tests\TestCase;

class RecordingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_when_user_starts_recoding_then_egress_request_sent_and_model_created(): void
    {
        $roomName = fake()->uuid();
        $egressId = fake()->word();
        $user = User::factory()->create();
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
                );
        });

        $response = $this->actingAs($user)
            ->postJson('recordings', [
                'room_name' => $roomName,
            ]);

        $recording = Recording::firstOrFail();
        $response
            ->assertStatus(201)
            ->assertJson(['id' => $recording->id]);
        $this->assertEquals(
            $recording->only('user_id', 'file_name', 'active', 'egress_id', 'updated_at'),
            [
                'user_id' => $user->id,
                'file_name' => $roomName . '-' . $recording->id . '.mp4',
                'active' => 1,
                'egress_id' => $egressId,
                'updated_at' => $recording->created_at,
            ]
        );
    }

    public function test_when_user_starts_recoding_but_egress_throws_error_then_dont_create_recording(): void
    {
        $roomName = fake()->uuid();
        $user = User::factory()->create();
        $this->partialMock(EgressServiceClient::class, function (MockInterface $mock) {
            $mock->shouldReceive('startRoomCompositeEgress')
                ->once()
                ->andThrow(new Exception());
        });

        $this->actingAs($user)
            ->postJson('recordings', [
                'room_name' => $roomName,
            ])
            ->assertInternalServerError();
        $this->assertDatabaseEmpty('recordings');
    }

    public function test_when_user_updates_recording_then_model_timestamp_updated(): void
    {
        $pastDateTime = Carbon::now()->subMinute();
        $user = User::factory()
            ->create();
        $recording = Recording::factory()
            ->state([
                'updated_at' => $pastDateTime,
            ])
            ->for($user)
            ->active()
            ->create();
        $futureDateTime = Carbon::now()->subHour();

        $this->actingAs($user)
            ->patch('recordings/' . $recording->id)
            ->assertNoContent();

        // Assert interval of minute so no microsecond test flakyness occurs
        $this->assertThat(
            Recording::firstOrFail()->updated_at,
            $this->logicalAnd(
                $this->greaterThan($pastDateTime),
                $this->greaterThan($futureDateTime),
            )
        );
    }

    public function test_when_user_stops_recording_then_egress_request_send_and_model_updated(): void
    {
        $egressId = fake()->word();
        $user = User::factory()
            ->create();
        $recording = Recording::factory()
            ->state([
                'egress_id' => $egressId,
            ])
            ->for($user)
            ->active()
            ->create();
        $this->partialMock(EgressServiceClient::class, function (MockInterface $mock) use ($egressId) {
            $mock->shouldReceive('stopEgress')
                ->once()
                ->withArgs(function (string $argumentEgressId) use ($egressId) {
                    return $argumentEgressId === $egressId;
                });
        });

        $this->actingAs($user)
            ->patch('recordings/' . $recording->id . '/stop')
            ->assertNoContent();
        $this->assertDatabaseHas('recordings', [
            'active' => false,
            'egress_id' => $egressId,
        ]);
    }
}
