<?php

declare(strict_types=1);

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Mobile\StoreVideoAccessRequest;
use App\Models\Center;
use App\Models\Course;
use App\Models\User;
use App\Models\Video;
use App\Services\Access\CourseAccessService;
use App\Services\VideoAccess\Contracts\VideoApprovalRequestServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VideoAccessRequestController extends Controller
{
    public function __construct(
        private readonly VideoApprovalRequestServiceInterface $service,
        private readonly CourseAccessService $courseAccessService
    ) {}

    public function store(StoreVideoAccessRequest $request, Center $center, Course $course, Video $video): JsonResponse
    {
        $student = $request->user();

        if (! $student instanceof User) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'Authentication required.',
                ],
            ], 401);
        }

        if ((int) $course->center_id !== (int) $center->id || ! $this->courseAccessService->isVideoInCourse($course, $video)) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Video not found.',
                ],
            ], 404);
        }

        $created = $this->service->createForStudent(
            student: $student,
            center: $center,
            course: $course,
            video: $video,
            reason: $request->input('reason')
        );

        return response()->json([
            'success' => true,
            'data' => [
                'request_id' => $created->id,
                'status' => 'pending',
            ],
        ]);
    }

    public function status(Request $request, Center $center, Course $course, Video $video): JsonResponse
    {
        $student = $request->user();

        if (! $student instanceof User) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'Authentication required.',
                ],
            ], 401);
        }

        if ((int) $course->center_id !== (int) $center->id || ! $this->courseAccessService->isVideoInCourse($course, $video)) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Video not found.',
                ],
            ], 404);
        }

        $status = $this->service->statusForStudent($student, $course, $video);

        return response()->json([
            'success' => true,
            'data' => $status,
        ]);
    }
}
