<?php

namespace Tests\Feature\Recordings;

use App\Models\Meeting;
use App\Models\Recording;
use App\Models\User;
use Database\Seeders\UserWithInactiveRecordingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class RecordingShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_when_user_requests_show_recording_then_return_streamed_recording_contents(): void
    {
        [$user, $recording, $fileContent] = app(UserWithInactiveRecordingSeeder::class)->run();

        $this
            ->actingAs($user)
            ->get('/recordings/' . $recording->id)
            ->assertSuccessful()
            ->assertStreamedContent($fileContent);
    }

    public function test_when_user_requests_show_recording_that_doesnt_belong_to_user_then_see_not_found_code(): void
    {
        [$_, $recording] = app(UserWithInactiveRecordingSeeder::class)->run();

        $this
            ->actingAs(User::factory()->create())
            ->get('/recordings/' . $recording->id)
            ->assertNotFound();
    }

    public function test_when_user_requests_show_recording_that_is_active_then_see_not_found(): void
    {
        $user = User::factory()->create();
        $meeting = Meeting::factory()
            ->create();
        $recording = Recording::factory()
            ->state(['active' => true])
            ->for($meeting)
            ->create();
        $user->meetings()->attach($meeting);

        $this
            ->actingAs($user)
            ->get('/recordings/' . $recording->id)
            ->assertNotFound();
    }
}
