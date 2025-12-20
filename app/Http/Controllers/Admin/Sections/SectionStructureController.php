<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Sections;

use App\Http\Controllers\Controller;
use App\Http\Requests\Sections\AttachPdfToSectionRequest;
use App\Http\Requests\Sections\AttachVideoToSectionRequest;
use App\Http\Requests\Sections\DetachPdfFromSectionRequest;
use App\Http\Requests\Sections\DetachVideoFromSectionRequest;
use App\Http\Resources\Sections\SectionPdfResource;
use App\Http\Resources\Sections\SectionVideoResource;
use App\Models\Course;
use App\Models\Pdf;
use App\Models\Section;
use App\Models\User;
use App\Models\Video;
use App\Services\Sections\Contracts\SectionStructureServiceInterface;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;

class SectionStructureController extends Controller
{
    public function __construct(
        private readonly SectionStructureServiceInterface $structureService
    ) {}

    public function videos(
        Course $course,
        Section $section
    ): JsonResponse {
        $admin = $this->requireAdmin();
        if ((int) $section->course_id !== (int) $course->id) {
            abort(404);
        }

        $videos = $this->structureService->listVideos($section, $admin);

        return response()->json([
            'success' => true,
            'data' => SectionVideoResource::collection($videos),
        ]);
    }

    public function showVideo(
        Course $course,
        Section $section,
        Video $video
    ): JsonResponse {
        $admin = $this->requireAdmin();
        if ((int) $section->course_id !== (int) $course->id || ! $section->videos()->whereKey($video->id)->exists()) {
            abort(404);
        }

        $videos = $this->structureService->listVideos($section, $admin);
        $found = $videos->firstWhere('id', $video->id);
        if ($found === null) {
            abort(404);
        }

        $video->setRelation('pivot', $found->pivot);

        return response()->json([
            'success' => true,
            'data' => new SectionVideoResource($video),
        ]);
    }

    public function attachVideo(
        Course $course,
        Section $section,
        AttachVideoToSectionRequest $request
    ): JsonResponse {
        $admin = $this->requireAdmin();
        if ((int) $section->course_id !== (int) $course->id) {
            abort(404);
        }

        $video = Video::findOrFail((int) $request->integer('video_id'));
        $this->structureService->attachVideo($section, $video, $admin);

        return response()->json([
            'success' => true,
            'data' => null,
        ], 201);
    }

    public function detachVideo(
        Course $course,
        Section $section,
        Video $video,
        DetachVideoFromSectionRequest $request
    ): JsonResponse {
        $admin = $this->requireAdmin();
        if ((int) $section->course_id !== (int) $course->id || ! $section->videos()->whereKey($video->id)->exists()) {
            abort(404);
        }

        $this->structureService->detachVideo($section, $video, $admin);

        return response()->json([
            'success' => true,
            'data' => null,
        ]);
    }

    public function pdfs(
        Course $course,
        Section $section
    ): JsonResponse {
        $admin = $this->requireAdmin();
        if ((int) $section->course_id !== (int) $course->id) {
            abort(404);
        }

        $pdfs = $this->structureService->listPdfs($section, $admin);

        return response()->json([
            'success' => true,
            'data' => SectionPdfResource::collection($pdfs),
        ]);
    }

    public function showPdf(
        Course $course,
        Section $section,
        Pdf $pdf
    ): JsonResponse {
        $admin = $this->requireAdmin();
        if ((int) $section->course_id !== (int) $course->id || ! $section->pdfs()->whereKey($pdf->id)->exists()) {
            abort(404);
        }

        $pdfs = $this->structureService->listPdfs($section, $admin);
        $found = $pdfs->firstWhere('id', $pdf->id);
        if ($found === null) {
            abort(404);
        }

        $pdf->setRelation('pivot', $found->pivot);

        return response()->json([
            'success' => true,
            'data' => new SectionPdfResource($pdf),
        ]);
    }

    public function attachPdf(
        Course $course,
        Section $section,
        AttachPdfToSectionRequest $request
    ): JsonResponse {
        $admin = $this->requireAdmin();
        if ((int) $section->course_id !== (int) $course->id) {
            abort(404);
        }

        $pdf = Pdf::findOrFail((int) $request->integer('pdf_id'));
        $this->structureService->attachPdf($section, $pdf, $admin);

        return response()->json([
            'success' => true,
            'data' => null,
        ], 201);
    }

    public function detachPdf(
        Course $course,
        Section $section,
        Pdf $pdf,
        DetachPdfFromSectionRequest $request
    ): JsonResponse {
        $admin = $this->requireAdmin();
        if ((int) $section->course_id !== (int) $course->id || ! $section->pdfs()->whereKey($pdf->id)->exists()) {
            abort(404);
        }

        $this->structureService->detachPdf($section, $pdf, $admin);

        return response()->json([
            'success' => true,
            'data' => null,
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
