<?php

namespace Tests\Feature\Recordings;

use App\Models\Meeting;
use App\Models\Recording;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class RecordingIndexTest extends TestCase
{
    use RefreshDatabase;

    #[TestWith([true])]
    #[TestWith([false])]
    public function test_when_user_requests_recordings_index_then_see_correct_json_response(
        bool $isAuthor,
    ): void {
        $user = User::factory()->create();
        $meeting = Meeting::factory()
            ->create();
        $recording = Recording::factory()
            ->state(['active' => false])
            ->for($meeting)
            ->for($isAuthor ? $user : User::factory())
            ->create();
        $user->meetings()->attach($meeting);

        $this->actingAs($user)
            ->get('recordings')
            ->assertSuccessful()
            ->assertJson([
                'data' => [[
                    'id' => $recording->id,
                    'is_author' => $isAuthor,
                    'file_name' => $recording->file_name,
                    'created_at' => $recording->created_at?->toJson(),
                ]],
            ]);
    }

    public function test_when_user_requests_recordings_then_user_cant_see_active_or_other_user_recordings(): void
    {
        $user = User::factory()->create();
        $meeting = Meeting::factory()
            ->has(Recording::factory()->state(['active' => true]))
            ->create();
        $user->meetings()->attach($meeting);
        Meeting::factory()
            ->has(Recording::factory()->state(['active' => false]))
            ->create();

        $this->actingAs($user)
            ->get('recordings')
            ->assertSuccessful()
            ->assertJsonCount(0, 'data');
    }

    public function test_when_user_is_super_user_then_user_can_list_all_recordings(): void
    {
        Recording::factory()
            ->inactive()
            ->state(['user_id' => User::factory()->create()->id])
            ->create();

        $this->actingAs(User::factory()->superUser()->create())
            ->get('recordings')
            ->assertSuccessful()
            ->assertJsonCount(1, 'data');
    }
}
