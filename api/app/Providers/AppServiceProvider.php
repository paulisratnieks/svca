<?php

namespace App\Providers;

use Agence104\LiveKit\AccessToken;
use Agence104\LiveKit\EgressServiceClient;
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
    }
}
