<?php

declare(strict_types=1);

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Mobile\PlaybackProgressRequest;
use App\Http\Requests\Mobile\RequestPlaybackRequest;
use App\Models\Center;
use App\Models\Course;
use App\Models\PlaybackSession;
use App\Models\User;
use App\Models\Video;
use App\Services\Playback\PlaybackService;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;

class PlaybackController extends Controller
{
    public function __construct(private readonly PlaybackService $playbackService) {}

    public function requestPlayback(
        RequestPlaybackRequest $request,
        Center $center,
        Course $course,
        Video $video
    ): JsonResponse {
        /** @var User $student */
        $student = $request->user();

        $payload = $this->playbackService->requestPlayback($student, $center, $course, $video);

        return response()->json([
            'success' => true,
            'data' => $payload,
        ]);
    }

    public function updateProgress(
        PlaybackProgressRequest $request,
        Center $center,
        Course $course,
        Video $video
    ): JsonResponse {
        /** @var User $student */
        $student = $request->user();
        /** @var array{session_id:int,percentage:int} $data */
        $data = $request->validated();

        $session = PlaybackSession::query()
            ->where('id', $data['session_id'])
            ->whereNull('deleted_at')
            ->first();

        if ($session === null) {
            $this->deny('SESSION_NOT_FOUND', 'Playback session not found.', 404);
        }

        $this->assertCourseContext($student, $center, $course, $video, $session);

        $this->playbackService->updateProgress($student, $session, $data['percentage']);

        return response()->json([
            'success' => true,
        ]);
    }

    private function assertCourseContext(
        User $student,
        Center $center,
        Course $course,
        Video $video,
        PlaybackSession $session
    ): void {
        if ((int) $course->center_id !== (int) $center->id) {
            $this->deny('NOT_FOUND', 'Course not found.', 404);
        }

        $videoInCourse = $course->videos()
            ->where('videos.id', $video->id)
            ->wherePivotNull('deleted_at')
            ->exists();

        if (! $videoInCourse || $session->video_id !== $video->id) {
            $this->deny('NOT_FOUND', 'Video not found.', 404);
        }

        if (is_numeric($student->center_id)) {
            if ((int) $student->center_id !== (int) $center->id) {
                $this->deny('CENTER_MISMATCH', 'Center mismatch.', 403);
            }
        } elseif ((int) $center->type !== 0) {
            $this->deny('CENTER_MISMATCH', 'Center mismatch.', 403);
        }
    }

    /**
     * @return never
     */
    private function deny(string $code, string $message, int $status): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'error' => [
                'code' => $code,
                'message' => $message,
            ],
        ], $status));
    }
}
