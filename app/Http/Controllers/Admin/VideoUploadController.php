<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Video\StoreVideoUploadRequest;
use App\Http\Requests\Video\UpdateVideoUploadStatusRequest;
use App\Models\Center;
use App\Models\Video;
use App\Models\VideoUploadSession;
use App\Services\Videos\VideoUploadService;
use Illuminate\Http\JsonResponse;

class VideoUploadController extends Controller
{
    public function __construct(private readonly VideoUploadService $service) {}

    public function store(StoreVideoUploadRequest $request): JsonResponse
    {
        /** @var \App\Models\User $admin */
        $admin = $request->user();

        /** @var Center $center */
        $center = Center::findOrFail((int) $request->input('center_id'));

        $video = null;
        if ($request->filled('video_id')) {
            $video = Video::findOrFail((int) $request->input('video_id'));
        }

        $session = $this->service->initializeUpload(
            admin: $admin,
            center: $center,
            originalFilename: (string) $request->input('original_filename'),
            video: $video
        );

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $session->id,
                'bunny_upload_id' => $session->bunny_upload_id,
                'upload_status' => $session->upload_status,
                'progress_percent' => $session->progress_percent,
            ],
        ], 201);
    }

    public function update(UpdateVideoUploadStatusRequest $request, VideoUploadSession $videoUploadSession): JsonResponse
    {
        $session = $this->service->transition(
            $videoUploadSession,
            (string) $request->input('status'),
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $session->id,
                'upload_status' => $session->upload_status,
                'progress_percent' => $session->progress_percent,
                'error_message' => $session->error_message,
            ],
        ]);
    }
}
