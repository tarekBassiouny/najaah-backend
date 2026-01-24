<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Videos;

use App\Http\Controllers\Concerns\AdminAuthenticates;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Videos\StoreVideoUploadSessionRequest;
use App\Models\Center;
use App\Models\Video;
use App\Services\Videos\Contracts\VideoUploadServiceInterface;
use Illuminate\Http\JsonResponse;

class VideoUploadSessionController extends Controller
{
    use AdminAuthenticates;

    public function __construct(private readonly VideoUploadServiceInterface $uploadService) {}

    public function store(StoreVideoUploadSessionRequest $request, Center $center): JsonResponse
    {
        $admin = $this->requireAdmin();
        $video = Video::findOrFail((int) $request->integer('video_id'));

        if ((int) $video->center_id !== (int) $center->id) {
            $this->notFound('Video not found.');
        }

        $session = $this->uploadService->initializeUpload(
            admin: $admin,
            center: $center,
            originalFilename: (string) $request->input('original_filename'),
            video: $video
        );

        /** @var string|null $tusUploadUrl */
        $tusUploadUrl = $session->getAttribute('tus_upload_url');
        /** @var array<string, string|int>|null $presignedHeaders */
        $presignedHeaders = $session->getAttribute('presigned_headers');
        /** @var \DateTimeInterface|null $expiresAt */
        $expiresAt = $session->expires_at;
        $expiresAtString = $expiresAt?->format(DATE_ATOM);

        return response()->json([
            'success' => true,
            'data' => [
                'upload_session_id' => $session->id,
                'provider' => 'bunny',
                'remote_id' => $session->bunny_upload_id,
                'upload_endpoint' => $tusUploadUrl,
                'presigned_headers' => $presignedHeaders,
                'expires_at' => $expiresAtString,
            ],
        ], 201);
    }
}
