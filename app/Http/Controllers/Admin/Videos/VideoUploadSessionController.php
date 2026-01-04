<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Videos;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Videos\StoreVideoUploadSessionRequest;
use App\Models\Center;
use App\Models\User;
use App\Models\Video;
use App\Services\Videos\VideoUploadService;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;

class VideoUploadSessionController extends Controller
{
    public function __construct(private readonly VideoUploadService $uploadService) {}

    public function store(StoreVideoUploadSessionRequest $request, Center $center): JsonResponse
    {
        $admin = $this->requireAdmin();
        $video = Video::findOrFail((int) $request->integer('video_id'));

        if ((int) $video->center_id !== (int) $center->id) {
            $this->notFound();
        }

        $session = $this->uploadService->initializeUpload(
            admin: $admin,
            center: $center,
            originalFilename: (string) $request->input('original_filename'),
            video: $video
        );

        /** @var string|null $uploadUrl */
        $uploadUrl = $session->getAttribute('upload_url');
        /** @var \DateTimeInterface|null $expiresAt */
        $expiresAt = $session->expires_at;
        $expiresAtString = $expiresAt?->format(DATE_ATOM);
        $accessKey = (string) config('bunny.api.api_key');

        return response()->json([
            'success' => true,
            'data' => [
                'upload_session_id' => $session->id,
                'provider' => 'bunny',
                'remote_id' => $session->bunny_upload_id,
                'upload_endpoint' => $uploadUrl,
                'required_headers' => [
                    'AccessKey' => $accessKey,
                ],
                'expires_at' => $expiresAtString,
            ],
        ], 201);
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

    private function notFound(): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'error' => [
                'code' => 'NOT_FOUND',
                'message' => 'Video upload session not found.',
            ],
        ], 404));
    }
}
