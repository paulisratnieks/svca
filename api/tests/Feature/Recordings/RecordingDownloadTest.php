<?php

namespace Tests\Feature\Recordings;

use App\Models\Meeting;
use App\Models\Recording;
use App\Models\User;
use Database\Seeders\UserWithInactiveRecordingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class RecordingDownloadTest extends TestCase
{
    use RefreshDatabase;

    public function test_when_user_requests_download_recording_then_return_streamed_download_recording(): void
    {
        [$user, $recording, $fileContent] = app(UserWithInactiveRecordingSeeder::class)->run();

        $this
            ->actingAs($user)
            ->get('/recordings/' . $recording->id . '/download')
            ->assertSuccessful()
            ->assertStreamedContent($fileContent)
            ->assertHeader('Content-Disposition', 'attachment; filename=' . $recording->file_name);
    }

    public function test_when_user_requests_download_recording_that_doesnt_belong_to_user_then_see_not_found_code(): void
    {
        [$_, $recording] = app(UserWithInactiveRecordingSeeder::class)->run();

        $this
            ->actingAs(User::factory()->create())
            ->get('/recordings/' . $recording->id . '/download')
            ->assertNotFound();
    }

    public function test_when_user_requests_download_recording_that_is_active_then_see_not_found(): void
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
            ->get('/recordings/' . $recording->id . '/download')
            ->assertNotFound();
    }
}
