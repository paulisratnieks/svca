<?php

namespace Recordings;

use Agence104\LiveKit\EgressServiceClient;
use App\Models\Meeting;
use App\Models\Recording;
use App\Models\User;
use Database\Seeders\UserWithInactiveRecordingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livekit\EgressInfo;
use Livekit\FileInfo;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class RecordingStopTest extends TestCase
{
    use RefreshDatabase;

    public function test_when_user_stops_recording_then_egress_request_send_and_model_updated(): void
    {
        $egressId = fake()->word();
        $user = User::factory()
            ->create();
        $recording = Recording::factory()
            ->state(['egress_id' => $egressId,])
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

    public function test_when_user_request_recording_stop_to_inactive_recording_then_not_found_returned(): void
    {
        $user = User::factory()->create();
        $recording = Recording::factory()
            ->state(['active' => false])
            ->for($user)
            ->create();

        $this->actingAs($user)
            ->patch('recordings/' . $recording->id)
            ->assertNotFound();
    }

    public function test_when_user_request_recording_stop_to_recording_that_doesnt_belong_to_user_then_not_found_returned(): void
    {
        $user = User::factory()->create();
        $recording = Recording::factory()
            ->for(User::factory())
            ->create();

        $this->actingAs($user)
            ->patch('recordings/' . $recording->id)
            ->assertNotFound();
    }
}
