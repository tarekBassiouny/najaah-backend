<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Sections;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Sections\ReorderSectionRequest;
use App\Http\Requests\Admin\Sections\StoreSectionRequest;
use App\Http\Requests\Admin\Sections\UpdateSectionRequest;
use App\Http\Resources\Admin\Sections\SectionCollection;
use App\Http\Resources\Admin\Sections\SectionResource;
use App\Models\Center;
use App\Models\Course;
use App\Models\Section;
use App\Models\User;
use App\Services\Centers\CenterScopeService;
use App\Services\Courses\Contracts\CourseStructureServiceInterface;
use App\Services\Sections\Contracts\SectionServiceInterface;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;

class SectionController extends Controller
{
    public function __construct(
        private readonly SectionServiceInterface $sectionService,
        private readonly CourseStructureServiceInterface $courseStructureService,
        private readonly CenterScopeService $centerScopeService
    ) {}

    /**
     * List sections.
     */
    public function index(
        Center $center,
        Course $course
    ): JsonResponse {
        $admin = $this->requireAdmin();
        $this->assertCourseBelongsToCenter($center, $course);
        $this->centerScopeService->assertAdminSameCenter($admin, $course);
        $sections = $this->sectionService->listForCourse((int) $course->id, $admin);

        return response()->json([
            'success' => true,
            'data' => new SectionCollection($sections),
        ]);
    }

    /**
     * Create a section.
     */
    public function store(
        StoreSectionRequest $request,
        Center $center,
        Course $course
    ): JsonResponse {
        $admin = $this->requireAdmin();
        $this->assertCourseBelongsToCenter($center, $course);
        /** @var array<string, mixed> $data */
        $data = $request->validated();
        $data['course_id'] = (int) $course->id;
        $section = $this->sectionService->create($data, $admin);

        return response()->json([
            'success' => true,
            'data' => new SectionResource($section->load(['videos', 'pdfs'])),
        ]);
    }

    /**
     * Show a section.
     */
    public function show(
        Center $center,
        Course $course,
        Section $section
    ): JsonResponse {
        $admin = $this->requireAdmin();
        $this->assertCourseBelongsToCenter($center, $course);
        $this->assertSectionBelongsToCourse($course, $section);

        $found = $this->sectionService->find((int) $section->id, $admin)?->load(['videos', 'pdfs']);

        return response()->json([
            'success' => true,
            'data' => $found !== null ? new SectionResource($found) : null,
        ]);
    }

    /**
     * Update a section.
     */
    public function update(
        UpdateSectionRequest $request,
        Center $center,
        Course $course,
        Section $section
    ): JsonResponse {
        $admin = $this->requireAdmin();
        $this->assertCourseBelongsToCenter($center, $course);
        $this->assertSectionBelongsToCourse($course, $section);
        /** @var array<string, mixed> $data */
        $data = $request->validated();
        $updated = $this->sectionService->update($section, $data, $admin)->load(['videos', 'pdfs']);

        return response()->json([
            'success' => true,
            'data' => new SectionResource($updated),
        ]);
    }

    /**
     * Delete a section.
     */
    public function destroy(
        Center $center,
        Course $course,
        Section $section
    ): JsonResponse {
        $admin = $this->requireAdmin();
        $this->assertCourseBelongsToCenter($center, $course);
        $this->assertSectionBelongsToCourse($course, $section);
        $this->sectionService->delete($section, $admin);

        return response()->json([
            'success' => true,
            'data' => null,
        ], 204);
    }

    /**
     * Restore a section.
     */
    public function restore(
        Center $center,
        Course $course,
        int $section
    ): JsonResponse {
        $admin = $this->requireAdmin();
        $this->assertCourseBelongsToCenter($center, $course);
        $found = Section::withTrashed()->findOrFail($section);
        $this->assertSectionBelongsToCourse($course, $found);
        $restored = $this->sectionService->restore($found, $admin)->load(['videos', 'pdfs']);

        return response()->json([
            'success' => true,
            'data' => new SectionResource($restored),
        ]);
    }

    /**
     * Reorder sections.
     */
    public function reorder(
        Center $center,
        Course $course,
        ReorderSectionRequest $request
    ): JsonResponse {
        $admin = $this->requireAdmin();
        $this->assertCourseBelongsToCenter($center, $course);
        $this->centerScopeService->assertAdminSameCenter($admin, $course);
        $validated = $request->validated();
        $sectionsInput = $validated['sections'] ?? [];

        if (! is_array($sectionsInput)) {
            return response()->json([
                'success' => false,
                'error' => 'Invalid sections payload',
            ], 422);
        }

        /** @var array<int, int> $ordered */
        $ordered = array_values(array_map('intval', $sectionsInput));

        $this->courseStructureService->reorderSections($course, $ordered, $admin);

        return response()->json([
            'success' => true,
            'data' => null,
        ]);
    }

    /**
     * Toggle section visibility.
     */
    public function toggleVisibility(
        Center $center,
        Course $course,
        Section $section
    ): JsonResponse {
        $admin = $this->requireAdmin();
        $this->assertCourseBelongsToCenter($center, $course);
        $this->assertSectionBelongsToCourse($course, $section);

        $section = $this->courseStructureService->toggleSectionVisibility($section, $admin);

        return response()->json([
            'success' => true,
            'data' => new SectionResource($section->fresh(['videos', 'pdfs'])),
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
