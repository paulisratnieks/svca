<?php

namespace Recordings;

use App\Models\Meeting;
use App\Models\Recording;
use App\Models\User;
use Database\Seeders\UserWithInactiveRecordingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RecordingDeleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_when_user_requests_recording_deletion_then_recording_file_and_model_is_deleted_and_successful_response(): void
    {
        $user = User::factory()->create();
        $meeting = Meeting::factory()
            ->create();
        $recording = Recording::factory()
            ->state(['active' => false])
            ->for($user)
            ->for($meeting)
            ->create();
        $user->meetings()->attach($meeting);
        Storage::fake('recordings');
        Storage::disk('recordings')->put($recording->file_name, fake()->text);


        $this
            ->actingAs($user)
            ->delete('/recordings/' . $recording->id)
            ->assertNoContent();
        $this->assertDatabaseMissing('recordings');
        Storage::disk('recordings')->assertMissing($recording->file_name);
    }

    public function test_when_user_request_recording_delete_to_active_recording_then_not_found_returned(): void
    {
        $user = User::factory()->create();
        $meeting = Meeting::factory()
            ->create();
        $recording = Recording::factory()
            ->state(['active' => true])
            ->for($meeting)
            ->create();
        $user->meetings()->attach($meeting);
        Storage::fake('recordings');
        Storage::disk('recordings')->put($recording->file_name, fake()->text);

        $this->actingAs($user)
            ->delete('recordings/' . $recording->id)
            ->assertNotFound();
        $this->assertDatabaseHas('recordings');
        Storage::disk('recordings')->assertExists($recording->file_name);
    }

    public function test_when_user_request_recording_delete_to_recording_that_doesnt_belong_to_user_then_not_found_returned(): void
    {
        [$user, $recording] = app(UserWithInactiveRecordingSeeder::class)->run();

        $this->actingAs(User::factory()->create())
            ->delete('recordings/' . $recording->id)
            ->assertNotFound();
        $this->assertDatabaseHas('recordings');
        Storage::disk('recordings')->assertExists($recording->file_name);
    }
}
