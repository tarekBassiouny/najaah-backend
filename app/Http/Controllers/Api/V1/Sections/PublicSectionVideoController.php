<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Sections;

use App\Http\Controllers\Controller;
use App\Http\Resources\Sections\SectionVideoResource;
use App\Models\Course;
use App\Models\Section;
use App\Models\Video;
use App\Services\Sections\Contracts\SectionStructureServiceInterface;
use Illuminate\Http\JsonResponse;

class PublicSectionVideoController extends Controller
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

        $videos = $this->structureService->listVideos($section);

        return response()->json([
            'success' => true,
            'data' => SectionVideoResource::collection($videos),
        ]);
    }

    public function show(
        Course $course,
        Section $section,
        Video $video
    ): JsonResponse {
        if ((int) $section->course_id !== (int) $course->id || ! $section->videos()->whereKey($video->id)->exists()) {
            abort(404);
        }

        $video->setRelation('pivot', $video->pivot);

        return response()->json([
            'success' => true,
            'data' => new SectionVideoResource($video),
        ]);
    }
}
