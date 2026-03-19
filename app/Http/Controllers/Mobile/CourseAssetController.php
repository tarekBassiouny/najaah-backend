<?php

declare(strict_types=1);

namespace App\Http\Controllers\Mobile;

use App\Enums\AIContentTargetType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Mobile\ListCourseAssetsRequest;
use App\Http\Requests\Mobile\TrackLearningAssetProgressRequest;
use App\Http\Resources\Mobile\CourseAsset\CourseAssetDetailResource;
use App\Http\Resources\Mobile\CourseAsset\CourseAssetListResource;
use App\Http\Resources\Mobile\LearningAssetProgressResource;
use App\Models\Course;
use App\Models\User;
use App\Services\Assessments\Contracts\MobileCourseAssetServiceInterface;
use Illuminate\Http\JsonResponse;

class CourseAssetController extends Controller
{
    public function __construct(
        private readonly MobileCourseAssetServiceInterface $courseAssetService
    ) {}

    public function index(ListCourseAssetsRequest $request, int $centerId, Course $course): JsonResponse
    {
        /** @var User|null $student */
        $student = $request->user();

        if (! $student instanceof User || $student->is_student === false) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'Only students can access course assets.',
                ],
            ], 403);
        }

        $type = $request->string('type')->toString();
        $assetType = $type !== '' ? AIContentTargetType::from($type) : null;

        $assets = $this->courseAssetService->listForCourse($student, $centerId, $course, $assetType);

        return response()->json([
            'success' => true,
            'message' => 'Operation completed',
            'data' => CourseAssetListResource::collection(collect($assets)),
        ]);
    }

    public function show(int $centerId, string $type, int $id): JsonResponse
    {
        /** @var User|null $student */
        $student = request()->user();

        if (! $student instanceof User || $student->is_student === false) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'Only students can access course assets.',
                ],
            ], 403);
        }

        $assetType = AIContentTargetType::tryFrom($type);

        if (! $assetType instanceof AIContentTargetType) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Course asset not found.',
                ],
            ], 404);
        }

        $asset = $this->courseAssetService->show($student, $centerId, $assetType, $id);

        return response()->json([
            'success' => true,
            'message' => 'Operation completed',
            'data' => new CourseAssetDetailResource($asset),
        ]);
    }

    public function trackProgress(
        TrackLearningAssetProgressRequest $request,
        int $centerId,
        string $type,
        int $id
    ): JsonResponse {
        /** @var User|null $student */
        $student = $request->user();

        if (! $student instanceof User || $student->is_student === false) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'Only students can access course assets.',
                ],
            ], 403);
        }

        $assetType = AIContentTargetType::tryFrom($type);

        if (! $assetType instanceof AIContentTargetType) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Course asset not found.',
                ],
            ], 404);
        }

        /** @var array<string, mixed> $payload */
        $payload = $request->validated();

        $progress = $this->courseAssetService->trackProgress($student, $centerId, $assetType, $id, $payload);

        return response()->json([
            'success' => true,
            'message' => 'Operation completed',
            'data' => new LearningAssetProgressResource($progress),
        ]);
    }
}
