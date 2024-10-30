<?php

namespace Tests\Feature;

use App\Models\Meeting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class MeetingViewTest extends TestCase
{
    use RefreshDatabase;

    #[TestWith(['18539908-930c-37ca-800e-d2274e6d8e2b', 204])]
    #[TestWith(['18539908-930c-37ca-800e-d2274e6d8e2c', 404])]
    public function test_user_joins_meeting_and_recieves_expected_response_code(string $meetingId, int $statusCode): void
    {
        $user = User::factory()->create();
        Meeting::factory()->create([
            'id' => '18539908-930c-37ca-800e-d2274e6d8e2b'
        ]);

        $this->actingAs($user)
            ->getJson('meetings/' . $meetingId)
            ->assertStatus($statusCode);
    }
}
