<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Videos;

use App\Http\Controllers\Concerns\AdminAuthenticates;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Videos\UploadTranscriptRequest;
use App\Models\Center;
use App\Models\Video;
use App\Services\Videos\Contracts\TranscriptServiceInterface;
use Illuminate\Http\JsonResponse;

class VideoTranscriptController extends Controller
{
    use AdminAuthenticates;

    public function __construct(
        private readonly TranscriptServiceInterface $transcriptService
    ) {}

    public function store(UploadTranscriptRequest $request, Center $center, Video $video): JsonResponse
    {
        $admin = $this->requireAdmin();
        $this->assertVideoBelongsToCenter($center, $video);

        if ($request->hasFile('file')) {
            /** @var \Illuminate\Http\UploadedFile $file */
            $file = $request->file('file');
            $updated = $this->transcriptService->uploadTranscript($video, $admin, $file);
        } else {
            $updated = $this->transcriptService->saveTranscriptText(
                $video,
                $admin,
                (string) $request->string('transcript_text')
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Transcript saved successfully.',
            'data' => $this->payload($updated),
        ]);
    }

    public function show(Center $center, Video $video): JsonResponse
    {
        $this->requireAdmin();
        $this->assertVideoBelongsToCenter($center, $video);

        return response()->json([
            'success' => true,
            'data' => $this->payload($video),
        ]);
    }

    public function destroy(Center $center, Video $video): JsonResponse
    {
        $admin = $this->requireAdmin();
        $this->assertVideoBelongsToCenter($center, $video);

        $updated = $this->transcriptService->deleteTranscript($video, $admin);

        return response()->json([
            'success' => true,
            'message' => 'Transcript deleted successfully.',
            'data' => $this->payload($updated),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(Video $video): array
    {
        return [
            'video_id' => $video->id,
            'has_transcript' => is_string($video->transcript) && trim($video->transcript) !== '',
            'transcript' => $video->transcript,
            'transcript_format' => $video->transcript_format?->value,
            'transcript_source' => $video->transcript_source?->value,
        ];
    }

    private function assertVideoBelongsToCenter(Center $center, Video $video): void
    {
        if ((int) $video->center_id !== (int) $center->id) {
            $this->notFound('Video not found.');
        }
    }
}
