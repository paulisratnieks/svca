<?php

namespace App\Providers;

use Agence104\LiveKit\AccessToken;
use Agence104\LiveKit\EgressServiceClient;
use App\Http\Controllers\RecordingController;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->app->bind(AccessToken::class, fn(): AccessToken => new AccessToken(
            (string) env('LIVEKIT_API_KEY'),
            (string) env('LIVEKIT_API_SECRET')
        ));

        $this->app->bind(EgressServiceClient::class, fn(): EgressServiceClient => new EgressServiceClient(
            (string) env('LIVEKIT_URL'),
            (string) env('LIVEKIT_API_KEY'),
            (string) env('LIVEKIT_API_SECRET'),
        ));

        $this->app->when(RecordingController::class)
            ->needs(Filesystem::class)
            ->give(fn(): Filesystem => Storage::disk('recordings'));
    }
}
