<?php

namespace App\Http\Controllers;

use Agence104\LiveKit\EgressServiceClient;
use App\Http\Requests\RecordingStoreRequest;
use App\Models\Recording;
use ErrorException;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Livekit\EncodedFileOutput;
use Livekit\EncodedFileType;
use Throwable;

class RecordingController extends Controller
{
    private const BaseRecordingPath = '/out/';
    private const RecordingLayout = 'grid';

    public function __construct(
        private readonly EgressServiceClient $egressServiceClient,
    ) {}

    /**
     * @throws Exception
     */
    public function store(RecordingStoreRequest $request): JsonResponse
    {
        $recording = Recording::create([
            'user_id' => auth()->id(),
            'active' => true,
        ]);

        try {
            $roomName = $request->validated('room_name');
            $request = $this->egressServiceClient
                ->startRoomCompositeEgress(
                    $request->string('room_name'),
                    self::RecordingLayout,
                    (new EncodedFileOutput())
                        ->setFilepath(self::BaseRecordingPath . $roomName . '-' . $recording->id)
                        ->setFileType(EncodedFileType::MP4)
                );

            $recording->file_name = $roomName . '-' . $recording->id . '.' . strtolower(EncodedFileType::name(EncodedFileType::MP4));
            $recording->egress_id = $request->getEgressId();
            $recording->save();
        } catch (Exception $exception) {
            $recording->delete();
            throw $exception;
        }

        return response()->json([
            'id' => $recording->id,
        ], 201);
    }

    public function update(string $recordingId): Response
    {
        Recording::query()
            ->ownedByMe()
            ->active()
            ->findOrFail($recordingId)
            ->touch();

        return response()->noContent();
    }

    /**
     * @throws Throwable
     */
    public function stop(string $recordingId): Response
    {
        $recording = Recording::query()
            ->ownedByMe()
            ->active()
            ->findOrFail($recordingId);

        $this->egressServiceClient
            ->stopEgress($recording->egress_id);

        $recording->updateOrFail(['active' => false]);

        return response()->noContent();
    }
}
