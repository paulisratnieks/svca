<?php

namespace Tests\Feature;

use App\Models\Meeting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MeetingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->be(User::factory()->create());
    }

    public function test_user_joins_meeting_and_receives_expected_response_code(): void
    {
        $this
            ->postJson('meetings')
            ->assertSuccessful()
            ->assertJson(['id' => Meeting::firstOrFail()->id]);
    }

    public function test_when_request_to_show_existing_recording_then_see_no_content_response(): void
    {
        $this
            ->getJson('meetings/' . Meeting::factory()->create()->id)
            ->assertNoContent();
    }

    public function test_when_request_to_show_non_existent_recording_then_see_not_found_response(): void
    {
        $this
            ->getJson('meetings/' . fake()->uuid())
            ->assertNotFound();
    }
}
