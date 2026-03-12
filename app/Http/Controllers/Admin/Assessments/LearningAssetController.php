<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Assessments;

use App\Enums\LearningAssetStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LearningAsset\ListLearningAssetsRequest;
use App\Http\Requests\Admin\LearningAsset\UpdateLearningAssetRequest;
use App\Http\Requests\Admin\LearningAsset\UpdateLearningAssetStatusRequest;
use App\Http\Resources\Admin\LearningAsset\LearningAssetResource;
use App\Models\Center;
use App\Models\Course;
use App\Models\LearningAsset;
use App\Models\User;
use App\Services\Assessments\LearningAssetAdminService;
use Illuminate\Http\JsonResponse;

class LearningAssetController extends Controller
{
    public function __construct(
        private readonly LearningAssetAdminService $learningAssetAdminService
    ) {}

    public function index(ListLearningAssetsRequest $request, Center $center, Course $course): JsonResponse
    {
        $this->assertCourseInCenter($center, $course);

        $assets = $this->learningAssetAdminService->listForCourse($center, $course, $request->validated());

        return response()->json([
            'success' => true,
            'data' => LearningAssetResource::collection($assets),
        ]);
    }

    public function show(Center $center, LearningAsset $asset): JsonResponse
    {
        $asset = $this->learningAssetAdminService->getInCenter($center, $asset);

        return response()->json([
            'success' => true,
            'data' => new LearningAssetResource($asset),
        ]);
    }

    public function update(UpdateLearningAssetRequest $request, Center $center, LearningAsset $asset): JsonResponse
    {
        $asset = $this->learningAssetAdminService->getInCenter($center, $asset);
        /** @var User|null $admin */
        $admin = $request->user();
        abort_unless($admin instanceof User, 401, 'Unauthorized');

        $updated = $this->learningAssetAdminService->update($asset, $request->validated(), $admin);

        return response()->json([
            'success' => true,
            'message' => 'Learning asset updated successfully.',
            'data' => new LearningAssetResource($updated),
        ]);
    }

    public function updateStatus(UpdateLearningAssetStatusRequest $request, Center $center, LearningAsset $asset): JsonResponse
    {
        $asset = $this->learningAssetAdminService->getInCenter($center, $asset);
        /** @var User|null $admin */
        $admin = $request->user();
        abort_unless($admin instanceof User, 401, 'Unauthorized');

        $updated = $this->learningAssetAdminService->updateStatus(
            $asset,
            LearningAssetStatus::from($request->integer('status')),
            $admin
        );

        return response()->json([
            'success' => true,
            'message' => 'Learning asset status updated successfully.',
            'data' => new LearningAssetResource($updated),
        ]);
    }

    private function assertCourseInCenter(Center $center, Course $course): void
    {
        abort_unless($course->center_id === $center->id, 404, 'Course not found in this center.');
    }
}
