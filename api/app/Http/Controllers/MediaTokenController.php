<?php

namespace App\Http\Controllers;

use Agence104\LiveKit\AccessToken;
use Agence104\LiveKit\AccessTokenOptions;
use Agence104\LiveKit\VideoGrant;
use App\Http\Requests\MediaTokenRequest;
use App\Models\Meeting;
use Exception;
use Illuminate\Http\JsonResponse;

class MediaTokenController extends Controller
{
    /**
     * @throws Exception
     */
    public function __invoke(
        MediaTokenRequest $request,
        AccessTokenOptions $accessTokenOptions,
        VideoGrant $videoGrant,
        AccessToken $accessToken
    ): JsonResponse {
        $meeting = Meeting::findOrFail($request->string('room_name'));

        $tokenOptions = $accessTokenOptions
            ->setIdentity((string) auth()->id());
        $videoGrant = $videoGrant
            ->setRoomJoin()
            ->setCanUpdateOwnMetadata()
            ->setRoomName(stringify($request->get('room_name')));
        $token = $accessToken
            ->init($tokenOptions)
            ->setGrant($videoGrant)
            ->toJwt();

        $meeting->participants()
            ->sync([auth()->id()]);

        return response()->json([
            'data' => $token,
        ]);
    }
}
