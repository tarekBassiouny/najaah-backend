<?php

declare(strict_types=1);

namespace App\Http\Controllers\Mobile;

use App\Enums\LearningAssetStatus;
use App\Enums\LearningAssetType;
use App\Http\Controllers\Controller;
use App\Http\Resources\Mobile\LearningAsset\LearningAssetDetailResource;
use App\Http\Resources\Mobile\LearningAsset\LearningAssetListResource;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\LearningAsset;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class LearningAssetController extends Controller
{
    public function summaries(int $centerId, Course $course): JsonResponse
    {
        return $this->listByType($centerId, $course, LearningAssetType::Summary);
    }

    public function summary(int $centerId, LearningAsset $asset): JsonResponse
    {
        return $this->showByType($centerId, $asset, LearningAssetType::Summary);
    }

    public function flashcards(int $centerId, Course $course): JsonResponse
    {
        return $this->listByType($centerId, $course, LearningAssetType::Flashcards);
    }

    public function flashcardSet(int $centerId, LearningAsset $asset): JsonResponse
    {
        return $this->showByType($centerId, $asset, LearningAssetType::Flashcards);
    }

    public function interactiveActivities(int $centerId, Course $course): JsonResponse
    {
        return $this->listByType($centerId, $course, LearningAssetType::InteractiveActivity);
    }

    public function interactiveActivity(int $centerId, LearningAsset $asset): JsonResponse
    {
        return $this->showByType($centerId, $asset, LearningAssetType::InteractiveActivity);
    }

    private function listByType(int $centerId, Course $course, LearningAssetType $type): JsonResponse
    {
        /** @var User|null $student */
        $student = request()->user();
        if (! $student instanceof User || $student->is_student === false) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'Only students can access learning assets.',
                ],
            ], 403);
        }

        if ($course->center_id !== $centerId) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Course not found.',
                ],
            ], 404);
        }

        $enrolled = Enrollment::query()
            ->where('user_id', $student->id)
            ->where('center_id', $centerId)
            ->where('course_id', $course->id)
            ->active()
            ->exists();

        if (! $enrolled) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_ENROLLED',
                    'message' => 'You are not enrolled in this course.',
                ],
            ], 403);
        }

        $assets = LearningAsset::query()
            ->where('center_id', $centerId)
            ->where('course_id', $course->id)
            ->where('asset_type', $type)
            ->published()
            ->orderByDesc('published_at')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Operation completed',
            'data' => LearningAssetListResource::collection($assets),
        ]);
    }

    private function showByType(int $centerId, LearningAsset $asset, LearningAssetType $type): JsonResponse
    {
        /** @var User|null $student */
        $student = request()->user();
        if (! $student instanceof User || $student->is_student === false) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'Only students can access learning assets.',
                ],
            ], 403);
        }

        if (
            $asset->center_id !== $centerId
            || $asset->asset_type !== $type
            || $asset->status !== LearningAssetStatus::Published
            || ! $asset->is_active
        ) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Learning asset not found.',
                ],
            ], 404);
        }

        $enrolled = Enrollment::query()
            ->where('user_id', $student->id)
            ->where('center_id', $centerId)
            ->where('course_id', $asset->course_id)
            ->active()
            ->exists();

        if (! $enrolled) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_ENROLLED',
                    'message' => 'You are not enrolled in this course.',
                ],
            ], 403);
        }

        return response()->json([
            'success' => true,
            'message' => 'Operation completed',
            'data' => new LearningAssetDetailResource($asset),
        ]);
    }
}
