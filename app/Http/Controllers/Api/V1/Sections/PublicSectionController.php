<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Sections;

use App\Http\Controllers\Controller;
use App\Http\Resources\Sections\SectionCollection;
use App\Http\Resources\Sections\SectionResource;
use App\Models\Course;
use App\Models\Section;
use App\Services\Sections\Contracts\SectionServiceInterface;
use Illuminate\Http\JsonResponse;

class PublicSectionController extends Controller
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

    public function show(
        Course $course,
        Section $section
    ): JsonResponse {
        if ((int) $section->course_id !== (int) $course->id) {
            abort(404);
        }

        $found = $this->sectionService->find((int) $section->id)?->load(['videos', 'pdfs']);
        if ($found === null) {
            abort(404);
        }

        return response()->json([
            'success' => true,
            'data' => new SectionResource($found),
        ]);
    }
}
