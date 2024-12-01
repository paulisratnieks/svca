<?php

namespace Tests\Feature;

use App\Models\Meeting;
use App\Models\Recording;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class MeetingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_joins_meeting_and_receives_expected_response_code(): void
    {
        $response = $this->actingAs(User::factory()->create())
            ->postJson('meetings');
        $response
            ->assertStatus(200)
            ->assertJson(['id' => Meeting::firstOrFail()->id]);
    }
}
