<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Video\StoreVideoUploadRequest;
use App\Http\Requests\Video\UpdateVideoUploadStatusRequest;
use App\Models\Center;
use App\Models\User;
use App\Models\Video;
use App\Models\VideoUploadSession;
use App\Services\Videos\VideoUploadService;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;

class VideoUploadController extends Controller
{
    public function __construct(
        private readonly VideoUploadService $service
    ) {}

    public function store(StoreVideoUploadRequest $request): JsonResponse
    {
        $admin = $this->requireAdmin();

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
                'video_id' => $session->bunny_upload_id,
                'library_id' => $session->library_id,
            ],
        ], 201);
    }

    public function update(UpdateVideoUploadStatusRequest $request, VideoUploadSession $videoUploadSession): JsonResponse
    {
        $admin = $this->requireAdmin();

        $session = $this->service->transition(
            $admin,
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

    private function requireAdmin(): User
    {
        $admin = request()->user();

        if (! $admin instanceof User) {
            throw new HttpResponseException(response()->json([
                'success' => false,
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'Authentication required.',
                ],
            ], 401));
        }

        return $admin;
    }
}
