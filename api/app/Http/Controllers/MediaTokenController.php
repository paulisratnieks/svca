<?php

namespace App\Http\Controllers;

use Agence104\LiveKit\AccessToken;
use Agence104\LiveKit\AccessTokenOptions;
use Agence104\LiveKit\VideoGrant;
use App\Http\Requests\MediaTokenRequest;
use Exception;
use Illuminate\Http\JsonResponse;

class MediaTokenController extends Controller
{
    /**
     * @throws Exception
     */
    public function __invoke(MediaTokenRequest $request): JsonResponse
    {
        $tokenOptions = (new AccessTokenOptions())
            ->setIdentity((string) auth()->id());

        $videoGrant = (new VideoGrant())
            ->setRoomJoin()
            ->setCanUpdateOwnMetadata()
            ->setRoomName($request->get('room_name'));

        $token = (new AccessToken(env('LIVEKIT_API_KEY'), env('LIVEKIT_API_SECRET')))
            ->init($tokenOptions)
            ->setGrant($videoGrant)
            ->toJwt();

        return response()->json([
            'data' => $token,
        ]);
    }
}
