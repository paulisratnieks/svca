<?php

namespace App\Http\Controllers;

use Agence104\LiveKit\EgressServiceClient;
use App\Http\Requests\RecordingStoreRequest;
use App\Models\Recording;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Livekit\EncodedFileOutput;
use Livekit\EncodedFileType;
use Livekit\FileInfo;
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
        $request = $this->egressServiceClient
            ->startRoomCompositeEgress(
                $request->string('room_name'),
                self::RecordingLayout,
                (new EncodedFileOutput())
                    ->setFilepath($this->recordingFileName($request->string('room_name')))
                    ->setFileType(EncodedFileType::MP4)
            );
        /**
         * @var FileInfo $recordingFileInfo
         */
        $recordingFileInfo = $request->getFileResults()->offsetGet(0);
        $recording = Recording::create([
            'user_id' => auth()->id(),
            'active' => true,
            'egress_id' => $request->getEgressId(),
            'file_name' => $recordingFileInfo->getFilename(),
        ]);

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

    private function recordingFileName(string $meetingId): string
    {
        return self::BaseRecordingPath . $meetingId . '-' . time();
    }
}
