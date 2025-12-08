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
use App\Services\Sections\Contracts\SectionServiceInterface;
use Illuminate\Http\JsonResponse;

class SectionController extends Controller
{
    public function __construct(
        private readonly SectionServiceInterface $sectionService
    ) {}

    public function index(
        Course $course
    ): JsonResponse {
        $sections = $this->sectionService->listForCourse((int) $course->id);

        return response()->json([
            'success' => true,
            'data' => new SectionCollection($sections),
        ]);
    }

    public function store(
        StoreSectionRequest $request,
        CreateSectionAction $createSectionAction
    ): JsonResponse {
        /** @var array<string, mixed> $data */
        $data = $request->validated();
        $section = $createSectionAction->execute($data);

        return response()->json([
            'success' => true,
            'data' => new SectionResource($section->load(['videos', 'pdfs'])),
        ]);
    }

    public function show(
        Course $course,
        Section $section
    ): JsonResponse {
        if ((int) $section->course_id !== (int) $course->id) {
            abort(404);
        }

        $found = $this->sectionService->find((int) $section->id)?->load(['videos', 'pdfs']);

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
        /** @var array<string, mixed> $data */
        $data = $request->validated();
        $updated = $updateSectionAction->execute($section, $data)->load(['videos', 'pdfs']);

        return response()->json([
            'success' => true,
            'data' => new SectionResource($updated),
        ]);
    }

    public function destroy(
        Section $section,
        DeleteSectionAction $deleteSectionAction
    ): JsonResponse {
        $deleteSectionAction->execute($section);

        return response()->json([
            'success' => true,
            'data' => null,
        ], 204);
    }

    public function restore(
        Section $section,
        RestoreSectionAction $restoreSectionAction
    ): JsonResponse {
        $restored = $restoreSectionAction->execute($section)->load(['videos', 'pdfs']);

        return response()->json([
            'success' => true,
            'data' => new SectionResource($restored),
        ]);
    }

    public function reorder(
        Course $course,
        ReorderSectionRequest $request
    ): JsonResponse {
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

        $sections = $course->sections()
            ->whereIn('id', $ordered)
            ->get()
            ->keyBy('id');

        foreach ($ordered as $index => $sectionId) {
            $section = $sections->get((int) $sectionId);
            if ($section === null) {
                continue;
            }

            $section->order_index = $index + 1;
            $section->save();
        }

        return response()->json([
            'success' => true,
            'data' => null,
        ]);
    }

    public function toggleVisibility(
        Course $course,
        Section $section
    ): JsonResponse {
        if ((int) $section->course_id !== (int) $course->id) {
            abort(404);
        }

        $section->visible = ! $section->visible;
        $section->save();

        return response()->json([
            'success' => true,
            'data' => new SectionResource($section->fresh(['videos', 'pdfs'])),
        ]);
    }
}
