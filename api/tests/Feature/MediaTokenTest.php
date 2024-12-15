<?php

namespace Tests\Feature;

use Agence104\LiveKit\AccessToken;
use App\Models\Meeting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Mockery\MockInterface;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class MediaTokenTest extends TestCase
{
    use RefreshDatabase;

    public function test_when_user_request_token_endpoint_then_receives_successful_token_response(): void
    {
        $token = fake()->word();
        $this->mockAccessTokenGenerator($token);

        $this
            ->actingAs(User::factory()->create())
            ->sendTokenRequest()
            ->assertSuccessful()
            ->assertJson(['data' => $token]);
    }

    public function test_when_user_request_token_endpoint_to_meeting_where_other_users_exist_then_see_record_added_to_pivot_table(): void
    {
        $this->mockAccessTokenGenerator(fake()->word());
        $otherUser = User::factory()->create();
        $meeting = Meeting::factory()->create();
        $otherUser->meetings()->attach($meeting->id);
        $user = User::factory()->create();

        $this
            ->actingAs($user)
            ->sendTokenRequest($meeting->id)
            ->assertSuccessful();
        $this->assertNotEmpty($otherUser->meetings);
        $this->assertNotEmpty($user->meetings);
    }

    /**
     * @return TestResponse<Response>
     */
    private function sendTokenRequest(?string $meetingId = null): TestResponse
    {
        return $this->getJson('/token?' . http_build_query(['room_name' => $meetingId ?: Meeting::factory()->create()->id]));
    }

    private function mockAccessTokenGenerator(string $token): void
    {
        $this->partialMock(AccessToken::class, function (MockInterface $mock) use ($token): void {
            $mock->shouldReceive('toJwt')
                ->once()
                ->andReturn($token);
        });
    }
}
