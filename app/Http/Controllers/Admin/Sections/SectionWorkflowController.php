<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Sections;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Sections\CreateSectionWithStructureRequest;
use App\Http\Requests\Admin\Sections\UpdateSectionWithStructureRequest;
use App\Http\Resources\Admin\Sections\SectionResource;
use App\Models\Center;
use App\Models\Course;
use App\Models\Section;
use App\Models\User;
use App\Services\Sections\Contracts\SectionWorkflowServiceInterface;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;

class SectionWorkflowController extends Controller
{
    public function __construct(
        private readonly SectionWorkflowServiceInterface $workflowService
    ) {}

    public function createWithStructure(
        CreateSectionWithStructureRequest $request,
        Center $center,
        Course $course
    ): JsonResponse {
        $admin = $this->requireAdmin();
        $this->assertCourseBelongsToCenter($center, $course);
        /** @var array<string, mixed> $data */
        $data = $request->validated();
        $data['course_id'] = (int) $course->id;
        /** @var array<int, int> $videos */
        $videos = is_array($data['videos'] ?? null) ? array_map('intval', $data['videos']) : [];
        /** @var array<int, int> $pdfs */
        $pdfs = is_array($data['pdfs'] ?? null) ? array_map('intval', $data['pdfs']) : [];
        $section = $this->workflowService->createWithStructure($admin, $data, $videos, $pdfs);

        return response()->json([
            'success' => true,
            'data' => new SectionResource($section->load(['videos', 'pdfs'])),
        ], 201);
    }

    public function updateWithStructure(
        UpdateSectionWithStructureRequest $request,
        Center $center,
        Course $course,
        Section $section
    ): JsonResponse {
        $admin = $this->requireAdmin();
        $this->assertCourseBelongsToCenter($center, $course);
        $this->assertSectionBelongsToCourse($course, $section);
        /** @var array<string, mixed> $data */
        $data = $request->validated();
        /** @var array<int, int> $videos */
        $videos = is_array($data['videos'] ?? null) ? array_map('intval', $data['videos']) : [];
        /** @var array<int, int> $pdfs */
        $pdfs = is_array($data['pdfs'] ?? null) ? array_map('intval', $data['pdfs']) : [];
        $updated = $this->workflowService->updateWithStructure(
            $admin,
            $section,
            $data,
            $videos,
            $pdfs
        )->load(['videos', 'pdfs']);

        return response()->json([
            'success' => true,
            'data' => new SectionResource($updated),
        ]);
    }

    public function deleteWithStructure(
        Center $center,
        Course $course,
        Section $section
    ): JsonResponse {
        $admin = $this->requireAdmin();
        $this->assertCourseBelongsToCenter($center, $course);
        $this->assertSectionBelongsToCourse($course, $section);
        $this->workflowService->deleteWithStructure($admin, $section);

        return response()->json([
            'success' => true,
            'data' => null,
        ], 204);
    }

    public function publish(
        Center $center,
        Course $course,
        Section $section
    ): JsonResponse {
        $admin = $this->requireAdmin();
        $this->assertCourseBelongsToCenter($center, $course);
        $this->assertSectionBelongsToCourse($course, $section);
        $published = $this->workflowService->publish($admin, $section)->load(['videos', 'pdfs']);

        return response()->json([
            'success' => true,
            'data' => new SectionResource($published),
        ]);
    }

    public function unpublish(
        Center $center,
        Course $course,
        Section $section
    ): JsonResponse {
        $admin = $this->requireAdmin();
        $this->assertCourseBelongsToCenter($center, $course);
        $this->assertSectionBelongsToCourse($course, $section);
        $unpublished = $this->workflowService->unpublish($admin, $section)->load(['videos', 'pdfs']);

        return response()->json([
            'success' => true,
            'data' => new SectionResource($unpublished),
        ]);
    }

    private function assertCourseBelongsToCenter(Center $center, Course $course): void
    {
        if ((int) $course->center_id !== (int) $center->id) {
            $this->notFound();
        }
    }

    private function assertSectionBelongsToCourse(Course $course, Section $section): void
    {
        if ((int) $section->course_id !== (int) $course->id) {
            $this->notFound();
        }
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
                'message' => 'Section not found.',
            ],
        ], 404));
    }
}
