<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Sections;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Sections\AttachPdfToSectionRequest;
use App\Http\Requests\Admin\Sections\AttachVideoToSectionRequest;
use App\Http\Requests\Admin\Sections\BulkAttachPdfsToSectionRequest;
use App\Http\Requests\Admin\Sections\BulkAttachVideosToSectionRequest;
use App\Http\Requests\Admin\Sections\BulkDetachPdfsFromSectionRequest;
use App\Http\Requests\Admin\Sections\BulkDetachVideosFromSectionRequest;
use App\Http\Requests\Admin\Sections\DetachPdfFromSectionRequest;
use App\Http\Requests\Admin\Sections\DetachVideoFromSectionRequest;
use App\Http\Resources\Admin\Sections\SectionPdfResource;
use App\Http\Resources\Admin\Sections\SectionVideoResource;
use App\Models\Center;
use App\Models\Course;
use App\Models\Pdf;
use App\Models\Section;
use App\Models\User;
use App\Models\Video;
use App\Services\Sections\Contracts\SectionStructureServiceInterface;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Throwable;

class SectionStructureController extends Controller
{
    public function __construct(
        private readonly SectionStructureServiceInterface $structureService
    ) {}

    /**
     * List section videos.
     */
    public function videos(
        Center $center,
        Course $course,
        Section $section
    ): JsonResponse {
        $admin = $this->requireAdmin();
        $this->assertCourseBelongsToCenter($center, $course);
        $this->assertSectionBelongsToCourse($course, $section);

        $videos = $this->structureService->listVideos($section, $admin);

        return response()->json([
            'success' => true,
            'message' => 'Section videos retrieved successfully',
            'data' => SectionVideoResource::collection($videos),
        ]);
    }

    /**
     * Show a section video.
     */
    public function showVideo(
        Center $center,
        Course $course,
        Section $section,
        Video $video
    ): JsonResponse {
        $admin = $this->requireAdmin();
        $this->assertCourseBelongsToCenter($center, $course);
        $this->assertSectionBelongsToCourse($course, $section);

        if (! $section->videos()->whereKey($video->id)->exists()) {
            $this->notFound();
        }

        $videos = $this->structureService->listVideos($section, $admin);
        $found = $videos->firstWhere('id', $video->id);
        if ($found === null) {
            $this->notFound();
        }

        $video->setRelation('pivot', $found->pivot);

        return response()->json([
            'success' => true,
            'message' => 'Section video retrieved successfully',
            'data' => new SectionVideoResource($video),
        ]);
    }

    /**
     * Attach a video to a section.
     */
    public function attachVideo(
        Center $center,
        Course $course,
        Section $section,
        AttachVideoToSectionRequest $request
    ): JsonResponse {
        $admin = $this->requireAdmin();
        $this->assertCourseBelongsToCenter($center, $course);
        $this->assertSectionBelongsToCourse($course, $section);

        $video = Video::findOrFail((int) $request->integer('video_id'));
        $this->structureService->attachVideo($section, $video, $admin);

        return response()->json([
            'success' => true,
            'message' => 'Video attached to section successfully',
            'data' => null,
        ], 201);
    }

    /**
     * Detach a video from a section.
     */
    public function detachVideo(
        Center $center,
        Course $course,
        Section $section,
        Video $video,
        DetachVideoFromSectionRequest $request
    ): JsonResponse {
        $admin = $this->requireAdmin();
        $this->assertCourseBelongsToCenter($center, $course);
        $this->assertSectionBelongsToCourse($course, $section);

        if (! $section->videos()->whereKey($video->id)->exists()) {
            $this->notFound();
        }

        $this->structureService->detachVideo($section, $video, $admin);

        return response()->json([
            'success' => true,
            'message' => 'Video detached from section successfully',
            'data' => null,
        ]);
    }

    /**
     * List section PDFs.
     */
    public function pdfs(
        Center $center,
        Course $course,
        Section $section
    ): JsonResponse {
        $admin = $this->requireAdmin();
        $this->assertCourseBelongsToCenter($center, $course);
        $this->assertSectionBelongsToCourse($course, $section);

        $pdfs = $this->structureService->listPdfs($section, $admin);

        return response()->json([
            'success' => true,
            'message' => 'Section PDFs retrieved successfully',
            'data' => SectionPdfResource::collection($pdfs),
        ]);
    }

    /**
     * Show a section PDF.
     */
    public function showPdf(
        Center $center,
        Course $course,
        Section $section,
        Pdf $pdf
    ): JsonResponse {
        $admin = $this->requireAdmin();
        $this->assertCourseBelongsToCenter($center, $course);
        $this->assertSectionBelongsToCourse($course, $section);

        if (! $section->pdfs()->whereKey($pdf->id)->exists()) {
            $this->notFound();
        }

        $pdfs = $this->structureService->listPdfs($section, $admin);
        $found = $pdfs->firstWhere('id', $pdf->id);
        if ($found === null) {
            $this->notFound();
        }

        $pdf->setRelation('pivot', $found->pivot);

        return response()->json([
            'success' => true,
            'message' => 'Section PDF retrieved successfully',
            'data' => new SectionPdfResource($pdf),
        ]);
    }

    /**
     * Attach a PDF to a section.
     */
    public function attachPdf(
        Center $center,
        Course $course,
        Section $section,
        AttachPdfToSectionRequest $request
    ): JsonResponse {
        $admin = $this->requireAdmin();
        $this->assertCourseBelongsToCenter($center, $course);
        $this->assertSectionBelongsToCourse($course, $section);

        $pdf = Pdf::findOrFail((int) $request->integer('pdf_id'));
        $this->structureService->attachPdf($section, $pdf, $admin);

        return response()->json([
            'success' => true,
            'message' => 'PDF attached to section successfully',
            'data' => null,
        ], 201);
    }

    /**
     * Detach a PDF from a section.
     */
    public function detachPdf(
        Center $center,
        Course $course,
        Section $section,
        Pdf $pdf,
        DetachPdfFromSectionRequest $request
    ): JsonResponse {
        $admin = $this->requireAdmin();
        $this->assertCourseBelongsToCenter($center, $course);
        $this->assertSectionBelongsToCourse($course, $section);

        if (! $section->pdfs()->whereKey($pdf->id)->exists()) {
            $this->notFound();
        }

        $this->structureService->detachPdf($section, $pdf, $admin);

        return response()->json([
            'success' => true,
            'message' => 'PDF detached from section successfully',
            'data' => null,
        ]);
    }

    /**
     * Bulk attach PDFs to a section.
     */
    public function bulkAttachPdfs(
        Center $center,
        Course $course,
        Section $section,
        BulkAttachPdfsToSectionRequest $request
    ): JsonResponse {
        $admin = $this->requireAdmin();
        $this->assertCourseBelongsToCenter($center, $course);
        $this->assertSectionBelongsToCourse($course, $section);

        /** @var array<int, int> $pdfIds */
        $pdfIds = $request->input('pdf_ids', []);

        $attached = [];
        $skipped = [];
        $failed = [];

        foreach ($pdfIds as $pdfId) {
            try {
                $pdf = Pdf::find($pdfId);
                if ($pdf === null) {
                    $failed[] = ['id' => $pdfId, 'reason' => 'PDF not found'];

                    continue;
                }

                if ($section->pdfs()->whereKey($pdfId)->exists()) {
                    $skipped[] = ['id' => $pdfId, 'reason' => 'Already attached'];

                    continue;
                }

                $this->structureService->attachPdf($section, $pdf, $admin);
                $attached[] = $pdfId;
            } catch (Throwable $e) {
                $failed[] = ['id' => $pdfId, 'reason' => $e->getMessage()];
            }
        }

        return response()->json([
            'success' => true,
            'message' => sprintf('%d PDF(s) attached successfully', count($attached)),
            'data' => [
                'attached' => count($attached),
                'skipped' => count($skipped),
                'failed' => count($failed),
                'details' => [
                    'attached_ids' => $attached,
                    'skipped' => $skipped,
                    'failed' => $failed,
                ],
            ],
        ], 201);
    }

    /**
     * Bulk detach PDFs from a section.
     */
    public function bulkDetachPdfs(
        Center $center,
        Course $course,
        Section $section,
        BulkDetachPdfsFromSectionRequest $request
    ): JsonResponse {
        $admin = $this->requireAdmin();
        $this->assertCourseBelongsToCenter($center, $course);
        $this->assertSectionBelongsToCourse($course, $section);

        /** @var array<int, int> $pdfIds */
        $pdfIds = $request->input('pdf_ids', []);

        $detached = [];
        $skipped = [];
        $failed = [];

        foreach ($pdfIds as $pdfId) {
            try {
                $pdf = Pdf::find($pdfId);
                if ($pdf === null) {
                    $failed[] = ['id' => $pdfId, 'reason' => 'PDF not found'];

                    continue;
                }

                if (! $section->pdfs()->whereKey($pdfId)->exists()) {
                    $skipped[] = ['id' => $pdfId, 'reason' => 'Not attached'];

                    continue;
                }

                $this->structureService->detachPdf($section, $pdf, $admin);
                $detached[] = $pdfId;
            } catch (Throwable $e) {
                $failed[] = ['id' => $pdfId, 'reason' => $e->getMessage()];
            }
        }

        return response()->json([
            'success' => true,
            'message' => sprintf('%d PDF(s) detached successfully', count($detached)),
            'data' => [
                'detached' => count($detached),
                'skipped' => count($skipped),
                'failed' => count($failed),
                'details' => [
                    'detached_ids' => $detached,
                    'skipped' => $skipped,
                    'failed' => $failed,
                ],
            ],
        ]);
    }

    /**
     * Bulk attach videos to a section.
     */
    public function bulkAttachVideos(
        Center $center,
        Course $course,
        Section $section,
        BulkAttachVideosToSectionRequest $request
    ): JsonResponse {
        $admin = $this->requireAdmin();
        $this->assertCourseBelongsToCenter($center, $course);
        $this->assertSectionBelongsToCourse($course, $section);

        /** @var array<int, int> $videoIds */
        $videoIds = $request->input('video_ids', []);

        $attached = [];
        $skipped = [];
        $failed = [];

        foreach ($videoIds as $videoId) {
            try {
                $video = Video::find($videoId);
                if ($video === null) {
                    $failed[] = ['id' => $videoId, 'reason' => 'Video not found'];

                    continue;
                }

                if ($section->videos()->whereKey($videoId)->exists()) {
                    $skipped[] = ['id' => $videoId, 'reason' => 'Already attached'];

                    continue;
                }

                $this->structureService->attachVideo($section, $video, $admin);
                $attached[] = $videoId;
            } catch (Throwable $e) {
                $failed[] = ['id' => $videoId, 'reason' => $e->getMessage()];
            }
        }

        return response()->json([
            'success' => true,
            'message' => sprintf('%d video(s) attached successfully', count($attached)),
            'data' => [
                'attached' => count($attached),
                'skipped' => count($skipped),
                'failed' => count($failed),
                'details' => [
                    'attached_ids' => $attached,
                    'skipped' => $skipped,
                    'failed' => $failed,
                ],
            ],
        ], 201);
    }

    /**
     * Bulk detach videos from a section.
     */
    public function bulkDetachVideos(
        Center $center,
        Course $course,
        Section $section,
        BulkDetachVideosFromSectionRequest $request
    ): JsonResponse {
        $admin = $this->requireAdmin();
        $this->assertCourseBelongsToCenter($center, $course);
        $this->assertSectionBelongsToCourse($course, $section);

        /** @var array<int, int> $videoIds */
        $videoIds = $request->input('video_ids', []);

        $detached = [];
        $skipped = [];
        $failed = [];

        foreach ($videoIds as $videoId) {
            try {
                $video = Video::find($videoId);
                if ($video === null) {
                    $failed[] = ['id' => $videoId, 'reason' => 'Video not found'];

                    continue;
                }

                if (! $section->videos()->whereKey($videoId)->exists()) {
                    $skipped[] = ['id' => $videoId, 'reason' => 'Not attached'];

                    continue;
                }

                $this->structureService->detachVideo($section, $video, $admin);
                $detached[] = $videoId;
            } catch (Throwable $e) {
                $failed[] = ['id' => $videoId, 'reason' => $e->getMessage()];
            }
        }

        return response()->json([
            'success' => true,
            'message' => sprintf('%d video(s) detached successfully', count($detached)),
            'data' => [
                'detached' => count($detached),
                'skipped' => count($skipped),
                'failed' => count($failed),
                'details' => [
                    'detached_ids' => $detached,
                    'skipped' => $skipped,
                    'failed' => $failed,
                ],
            ],
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
