<?php

namespace Tests\Feature;

use Agence104\LiveKit\AccessToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class MediaTokenTest extends TestCase
{
    use RefreshDatabase;

    public function test_when_user_request_token_endpoint_then_recieves_successful_token_response(): void
    {
        $token = fake()->word();
        $this->partialMock(AccessToken::class, function (MockInterface $mock) use ($token): void {
            $mock->shouldReceive('toJwt')
                ->once()
                ->andReturn($token);
        });

        $this
            ->actingAs(User::factory()->create())
            ->getJson('/token?' . http_build_query(['room_name' => fake()->uuid()]))
            ->assertSuccessful()
            ->assertJson(['data' => $token]);
    }
}
