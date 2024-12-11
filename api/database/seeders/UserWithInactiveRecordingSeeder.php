<?php

namespace Database\Seeders;

use App\Models\Meeting;
use App\Models\Recording;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class UserWithInactiveRecordingSeeder extends Seeder
{
    public function run(): array
    {
        $user = User::factory()->create();
        $meeting = Meeting::factory()
            ->create();
        $recording = Recording::factory()
            ->state(['active' => false])
            ->for($meeting)
            ->create();
        $user->meetings()->attach($meeting);

        $fileContent = fake()->text;
        Storage::fake('recordings');
        Storage::disk('recordings')->put($recording->file_name, $fileContent);

        return [$user, $recording, $fileContent];
    }
}
