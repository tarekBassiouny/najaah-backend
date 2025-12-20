<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Sections;

use App\Actions\Sections\CreateSectionAction;
use App\Actions\Sections\DeleteSectionAction;
use App\Actions\Sections\RestoreSectionAction;
use App\Actions\Sections\UpdateSectionAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Sections\ReorderSectionRequest;
use App\Http\Requests\Sections\StoreSectionRequest;
use App\Http\Requests\Sections\UpdateSectionRequest;
use App\Http\Resources\Sections\SectionCollection;
use App\Http\Resources\Sections\SectionResource;
use App\Models\Course;
use App\Models\Section;
use App\Models\User;
use App\Services\Courses\Contracts\CourseStructureServiceInterface;
use App\Services\Sections\Contracts\SectionServiceInterface;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;

class SectionController extends Controller
{
    public function __construct(
        private readonly SectionServiceInterface $sectionService,
        private readonly CourseStructureServiceInterface $courseStructureService
    ) {}

    public function index(
        Course $course
    ): JsonResponse {
        $admin = $this->requireAdmin();
        $sections = $this->sectionService->listForCourse((int) $course->id, $admin);

        return response()->json([
            'success' => true,
            'data' => new SectionCollection($sections),
        ]);
    }

    public function store(
        StoreSectionRequest $request,
        CreateSectionAction $createSectionAction
    ): JsonResponse {
        $admin = $this->requireAdmin();
        /** @var array<string, mixed> $data */
        $data = $request->validated();
        $section = $createSectionAction->execute($admin, $data);

        return response()->json([
            'success' => true,
            'data' => new SectionResource($section->load(['videos', 'pdfs'])),
        ]);
    }

    public function show(
        Course $course,
        Section $section
    ): JsonResponse {
        $admin = $this->requireAdmin();
        if ((int) $section->course_id !== (int) $course->id) {
            abort(404);
        }

        $found = $this->sectionService->find((int) $section->id, $admin)?->load(['videos', 'pdfs']);

        return response()->json([
            'success' => true,
            'data' => $found !== null ? new SectionResource($found) : null,
        ]);
    }

    public function update(
        UpdateSectionRequest $request,
        Section $section,
        UpdateSectionAction $updateSectionAction
    ): JsonResponse {
        $admin = $this->requireAdmin();
        /** @var array<string, mixed> $data */
        $data = $request->validated();
        $updated = $updateSectionAction->execute($admin, $section, $data)->load(['videos', 'pdfs']);

        return response()->json([
            'success' => true,
            'data' => new SectionResource($updated),
        ]);
    }

    public function destroy(
        Section $section,
        DeleteSectionAction $deleteSectionAction
    ): JsonResponse {
        $admin = $this->requireAdmin();
        $deleteSectionAction->execute($admin, $section);

        return response()->json([
            'success' => true,
            'data' => null,
        ], 204);
    }

    public function restore(
        Section $section,
        RestoreSectionAction $restoreSectionAction
    ): JsonResponse {
        $admin = $this->requireAdmin();
        $restored = $restoreSectionAction->execute($admin, $section)->load(['videos', 'pdfs']);

        return response()->json([
            'success' => true,
            'data' => new SectionResource($restored),
        ]);
    }

    public function reorder(
        Course $course,
        ReorderSectionRequest $request
    ): JsonResponse {
        $admin = $this->requireAdmin();
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

    public function toggleVisibility(
        Course $course,
        Section $section
    ): JsonResponse {
        $admin = $this->requireAdmin();
        if ((int) $section->course_id !== (int) $course->id) {
            abort(404);
        }

        $section = $this->courseStructureService->toggleSectionVisibility($section, $admin);

        return response()->json([
            'success' => true,
            'data' => new SectionResource($section->fresh(['videos', 'pdfs'])),
        ]);
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
}
