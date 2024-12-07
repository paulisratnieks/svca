<?php

namespace App\Http\Controllers;

use Agence104\LiveKit\EgressServiceClient;
use App\Http\Requests\RecordingStoreRequest;
use App\Http\Resources\RecordingResource;
use App\Models\Recording;
use Exception;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Livekit\EgressInfo;
use Livekit\EncodedFileOutput;
use Livekit\EncodedFileType;
use Livekit\FileInfo;
use Str;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class RecordingController extends Controller
{
    private const BaseRecordingPath = '/out/';
    private const RecordingLayout = 'grid';

    public function __construct(
        private readonly EgressServiceClient $egressServiceClient,
        private readonly Filesystem $filesystem,
    ) {}

    public function index(Authenticatable $user): AnonymousResourceCollection
    {
        return RecordingResource::collection($user->recordings()->inactive()->get());
    }

    public function show(Recording $recording, Authenticatable $user): StreamedResponse
    {
        abort_if($user->cannot('view', $recording), ResponseAlias::HTTP_NOT_FOUND);

        return response()->stream(function () use ($recording) {
            echo $this->filesystem->get($recording->file_name);
        });
    }

    public function download(Recording $recording, Authenticatable $user): StreamedResponse
    {
        abort_if($user->cannot('view', $recording), ResponseAlias::HTTP_NOT_FOUND);

        return response()->streamDownload(function () use ($recording) {
            echo $this->filesystem->get($recording->file_name);
        }, $recording->file_name);
    }

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
                    ->setFilepath($this->recordingFilePath($request->string('room_name')))
                    ->setFileType(EncodedFileType::MP4)
            );
        $recording = Recording::create([
            'user_id' => auth()->id(),
            'meeting_id' => $request->getRoomName(),
            'active' => true,
            'egress_id' => $request->getEgressId(),
            'file_name' => $this->recordingFileName($request),
        ]);

        return response()->json([
            'id' => $recording->id,
        ], ResponseAlias::HTTP_CREATED);
    }

    public function update(Recording $recording, Authenticatable $user): Response
    {
        abort_if($user->cannot('update', $recording), ResponseAlias::HTTP_NOT_FOUND);

        $recording->touch();

        return response()->noContent();
    }

    /**
     * @throws Throwable
     */
    public function stop(Recording $recording, Authenticatable $user): Response
    {
        abort_if($user->cannot('update', $recording), ResponseAlias::HTTP_NOT_FOUND);

        $this->egressServiceClient
            ->stopEgress($recording->egress_id);

        $recording->updateOrFail(['active' => false]);

        return response()->noContent();
    }

    public function destroy(Recording $recording, Authenticatable $user): Response
    {
        abort_if($user->cannot('delete', $recording), ResponseAlias::HTTP_NOT_FOUND);

        $this->filesystem->delete($recording->file_name);
        $recording->delete();

        return response()->noContent();
    }

    private function recordingFilePath(string $meetingId): string
    {
        return self::BaseRecordingPath . $meetingId . '-' . time();
    }

    private function recordingFileName(EgressInfo $egressInfo): string
    {
        /**
         * @var FileInfo $recordingFileInfo
         */
        $recordingFileInfo = $egressInfo->getFileResults()->offsetGet(0);

        return Str::of($recordingFileInfo->getFilename())
            ->afterLast('/');
    }
}
