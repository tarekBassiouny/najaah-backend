<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pdf\StorePdfRequest;
use App\Http\Resources\PdfResource;
use App\Models\Course;
use App\Models\Section;
use App\Models\Video;
use App\Services\Pdfs\PdfStorageService;
use Illuminate\Http\JsonResponse;

class PdfUploadController extends Controller
{
    public function __construct(private readonly PdfStorageService $storageService) {}

    public function store(StorePdfRequest $request): JsonResponse
    {
        $course = $this->resolveCourse($request->input('course_id'));
        $section = $this->resolveSection($request->input('section_id'), $course);
        $video = $this->resolveVideo($request->input('video_id'), $course);

        $pdf = $this->storageService->upload(
            $request->file('file'),
            $request->validated(),
            $request->user(),
            $course,
            $section,
            $video
        );

        return response()->json([
            'success' => true,
            'message' => 'PDF uploaded successfully',
            'data' => new PdfResource($pdf),
        ], 201);
    }

    private function resolveCourse(int|string|null $courseId): ?Course
    {
        if ($courseId === null) {
            return null;
        }

        /** @var Course|null $course */
        $course = Course::find($courseId);

        return $course;
    }

    private function resolveSection(int|string|null $sectionId, ?Course $course): ?Section
    {
        if ($sectionId === null) {
            return null;
        }

        /** @var Section|null $section */
        $section = Section::find($sectionId);

        if ($section !== null && $course !== null && (int) $section->course_id !== (int) $course->id) {
            abort(422, 'Section does not belong to the provided course.');
        }

        return $section;
    }

    private function resolveVideo(int|string|null $videoId, ?Course $course): ?Video
    {
        if ($videoId === null) {
            return null;
        }

        /** @var Video|null $video */
        $video = Video::find($videoId);

        if ($video !== null && $course !== null && ! $course->videos()->whereKey($video->id)->exists()) {
            abort(422, 'Video does not belong to the provided course.');
        }

        return $video;
    }
}
