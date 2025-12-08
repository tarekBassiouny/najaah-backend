<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Sections;

use App\Http\Controllers\Controller;
use App\Http\Resources\Sections\SectionPdfResource;
use App\Models\Course;
use App\Models\Pdf;
use App\Models\Section;
use App\Services\Sections\Contracts\SectionStructureServiceInterface;
use Illuminate\Http\JsonResponse;

class PublicSectionPdfController extends Controller
{
    public function __construct(
        private readonly SectionStructureServiceInterface $structureService
    ) {}

    public function index(
        Course $course,
        Section $section
    ): JsonResponse {
        if ((int) $section->course_id !== (int) $course->id) {
            abort(404);
        }

        $pdfs = $this->structureService->listPdfs($section);

        return response()->json([
            'success' => true,
            'data' => SectionPdfResource::collection($pdfs),
        ]);
    }

    public function show(
        Course $course,
        Section $section,
        Pdf $pdf
    ): JsonResponse {
        if ((int) $section->course_id !== (int) $course->id || ! $section->pdfs()->whereKey($pdf->id)->exists()) {
            abort(404);
        }

        $pdf->setRelation('pivot', $pdf->pivot);

        return response()->json([
            'success' => true,
            'data' => new SectionPdfResource($pdf),
        ]);
    }
}
