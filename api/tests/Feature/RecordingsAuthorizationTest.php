<?php

namespace Tests\Feature;

use App\Models\Recording;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RecordingsAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('endpointDataProvider')]
    public function test_user_cant_interact_with_inactive_recordings(string $endpoint): void
    {
        $user = User::factory()
            ->has(
                Recording::factory()
                ->inactive()
            )
            ->create();

        $this->actingAs($user)
            ->patch('recordings/1/' . $endpoint)
            ->assertNotFound();
    }

    #[DataProvider('endpointDataProvider')]
    public function test_user_that_is_not_author_cant_interact_with_recording(string $endpoint): void
    {
        $user = User::factory()->create();
        User::factory()
            ->has(
                Recording::factory()
                ->active()
            )
            ->create();

        $this->actingAs($user)
            ->patch('recordings/1/' . $endpoint)
            ->assertNotFound();
    }

    /**
     * @return array<string, string[]>
     */
    public static function endpointDataProvider(): array
    {
        return [
            'update endpoint' => [''],
            'stop endpoint' => ['stop'],
        ];
    }
}
