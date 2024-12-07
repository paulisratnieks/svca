<?php

namespace Recordings;

use App\Models\Recording;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class RecordingUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_when_user_updates_recording_then_model_timestamp_updated(): void
    {
        $pastDateTime = Carbon::now()->subMinute();
        $futureDateTime = Carbon::now()->addMinute();
        $user = User::factory()
            ->create();
        $recording = Recording::factory()
            ->state([
                'updated_at' => $pastDateTime,
            ])
            ->for($user)
            ->active()
            ->create();

        $this->actingAs($user)
            ->patch('recordings/' . $recording->id)
            ->assertNoContent();

        // Assert interval of minute so no microsecond test flakyness occurs
        $this->assertThat(
            Recording::firstOrFail()->updated_at,
            $this->logicalAnd(
                $this->greaterThan($pastDateTime),
                $this->lessThan($futureDateTime),
            )
        );
    }

    public function test_when_user_request_recording_update_to_inactive_recording_then_not_found_returned(): void
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

    public function test_when_user_request_recording_update_to_recording_that_doesnt_belong_to_user_then_not_found_returned(): void
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
