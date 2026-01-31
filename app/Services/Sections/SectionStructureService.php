<?php

declare(strict_types=1);

namespace App\Services\Sections;

use App\Exceptions\AttachmentNotAllowedException;
use App\Models\Pdf;
use App\Models\Pivots\CoursePdf;
use App\Models\Pivots\CourseVideo;
use App\Models\Section;
use App\Models\User;
use App\Models\Video;
use App\Services\Access\AttachmentAccessService;
use App\Services\Access\PdfAccessService;
use App\Services\Access\VideoAccessService;
use App\Services\Audit\AuditLogService;
use App\Services\Centers\CenterScopeService;
use App\Services\Sections\Contracts\SectionStructureServiceInterface;
use App\Support\AuditActions;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SectionStructureService implements SectionStructureServiceInterface
{
    public function __construct(
        private readonly CenterScopeService $centerScopeService,
        private readonly AttachmentAccessService $attachmentAccessService,
        private readonly VideoAccessService $videoAccessService,
        private readonly PdfAccessService $pdfAccessService,
        private readonly AuditLogService $auditLogService
    ) {}

    /** @return Collection<int, Video> */
    public function listVideos(Section $section, ?User $actor = null): Collection
    {
        $this->assertCenterScope($section, $actor);

        return $section->videos()
            ->orderBy('course_video.order_index')
            ->get();
    }

    /** @return Collection<int, Pdf> */
    public function listPdfs(Section $section, ?User $actor = null): Collection
    {
        $this->assertCenterScope($section, $actor);

        return $section->pdfs()
            ->orderBy('course_pdf.order_index')
            ->get();
    }

    public function attachVideo(Section $section, Video $video, ?User $actor = null): void
    {
        $this->assertCenterScope($section, $actor);
        $this->assertSectionActive($section);
        $this->assertVideoBelongsToCourse($section, $video);
        $this->videoAccessService->assertReadyForAttachment($video);

        $pivot = CourseVideo::withTrashed()
            ->where('video_id', $video->id)
            ->where('course_id', $section->course_id)
            ->first();

        if ($pivot !== null && $pivot->course_id !== $section->course_id) {
            throw new AttachmentNotAllowedException('Video does not belong to this course.', 422);
        }

        $order = $this->nextVideoOrder($section);
        $previousSectionId = $pivot?->section_id;

        if ($pivot === null) {
            CourseVideo::create([
                'course_id' => $section->course_id,
                'video_id' => $video->id,
                'section_id' => $section->id,
                'order_index' => $order,
                'visible' => true,
                'view_limit_override' => null,
            ]);

            $this->auditLogService->log($actor, $section, AuditActions::SECTION_VIDEO_ATTACHED, [
                'video_id' => $video->id,
            ]);

            return;
        }

        $pivot->section_id = $section->id;
        $pivot->order_index = $order;
        $pivot->visible = true;

        if ($pivot->trashed()) {
            $pivot->restore();
        }

        $pivot->save();

        if ($previousSectionId !== null && is_numeric($previousSectionId) && $previousSectionId !== $section->id) {
            $this->syncVideoOrderForSection((int) $section->course_id, (int) $previousSectionId);
        }

        $this->auditLogService->log($actor, $section, AuditActions::SECTION_VIDEO_ATTACHED, [
            'video_id' => $video->id,
        ]);
    }

    public function detachVideo(Section $section, Video $video, ?User $actor = null): void
    {
        $this->assertCenterScope($section, $actor);
        $pivot = CourseVideo::query()
            ->forCourseId((int) $section->course_id)
            ->forVideo($video)
            ->forSectionId((int) $section->id)
            ->first();

        if ($pivot === null) {
            return;
        }

        $pivot->section_id = null;
        $pivot->order_index = $this->nextVideoOrder($section);
        $pivot->save();

        $this->syncVideoOrder($section, $this->currentVideoIds($section));

        $this->auditLogService->log($actor, $section, AuditActions::SECTION_VIDEO_DETACHED, [
            'video_id' => $video->id,
        ]);
    }

    public function attachPdf(Section $section, Pdf $pdf, ?User $actor = null): void
    {
        $this->assertCenterScope($section, $actor);
        $this->assertSectionActive($section);
        $this->assertPdfBelongsToCourse($section, $pdf);
        $this->pdfAccessService->assertReadyForAttachment($pdf);

        $pivot = CoursePdf::withTrashed()
            ->where('pdf_id', $pdf->id)
            ->where('course_id', $section->course_id)
            ->first();

        if ($pivot !== null && $pivot->course_id !== $section->course_id) {
            throw new AttachmentNotAllowedException('PDF does not belong to this course.', 422);
        }

        $order = $this->nextPdfOrder($section);
        $previousSectionId = $pivot?->section_id;

        if ($pivot === null) {
            CoursePdf::create([
                'course_id' => $section->course_id,
                'pdf_id' => $pdf->id,
                'section_id' => $section->id,
                'video_id' => null,
                'order_index' => $order,
                'visible' => true,
            ]);

            $this->auditLogService->log($actor, $section, AuditActions::SECTION_PDF_ATTACHED, [
                'pdf_id' => $pdf->id,
            ]);

            return;
        }

        $pivot->section_id = $section->id;
        $pivot->video_id = null;
        $pivot->order_index = $order;
        $pivot->visible = true;

        if ($pivot->trashed()) {
            $pivot->restore();
        }

        $pivot->save();

        if ($previousSectionId !== null && is_numeric($previousSectionId) && $previousSectionId !== $section->id) {
            $this->syncPdfOrderForSection((int) $section->course_id, (int) $previousSectionId);
        }

        $this->auditLogService->log($actor, $section, AuditActions::SECTION_PDF_ATTACHED, [
            'pdf_id' => $pdf->id,
        ]);
    }

    public function detachPdf(Section $section, Pdf $pdf, ?User $actor = null): void
    {
        $this->assertCenterScope($section, $actor);
        $pivot = CoursePdf::query()
            ->forCourseId((int) $section->course_id)
            ->forPdf($pdf)
            ->forSectionId((int) $section->id)
            ->first();

        if ($pivot === null) {
            return;
        }

        $pivot->section_id = null;
        $pivot->order_index = $this->nextPdfOrder($section);
        $pivot->save();

        $this->syncPdfOrder($section, $this->currentPdfIds($section));

        $this->auditLogService->log($actor, $section, AuditActions::SECTION_PDF_DETACHED, [
            'pdf_id' => $pdf->id,
        ]);
    }

    /** @param array<int, int> $orderedIds */
    public function syncVideoOrder(Section $section, array $orderedIds): void
    {
        DB::transaction(function () use ($section, $orderedIds): void {
            $pivots = CourseVideo::query()
                ->forCourseId((int) $section->course_id)
                ->forSectionId((int) $section->id)
                ->notDeleted()
                ->whereIn('video_id', $orderedIds)
                ->get()
                ->keyBy('video_id');

            foreach (array_values($orderedIds) as $index => $videoId) {
                if (! $pivots->has($videoId)) {
                    continue;
                }

                /** @var CourseVideo $pivot */
                $pivot = $pivots->get($videoId);
                $pivot->order_index = $index + 1;
                $pivot->save();
            }
        });
    }

    /** @param array<int, int> $orderedIds */
    public function syncPdfOrder(Section $section, array $orderedIds): void
    {
        DB::transaction(function () use ($section, $orderedIds): void {
            $pivots = CoursePdf::query()
                ->forCourseId((int) $section->course_id)
                ->forSectionId((int) $section->id)
                ->notDeleted()
                ->whereIn('pdf_id', $orderedIds)
                ->get()
                ->keyBy('pdf_id');

            foreach (array_values($orderedIds) as $index => $pdfId) {
                if (! $pivots->has($pdfId)) {
                    continue;
                }

                /** @var CoursePdf $pivot */
                $pivot = $pivots->get($pdfId);
                $pivot->order_index = $index + 1;
                $pivot->save();
            }
        });
    }

    /** @return array<int, int> */
    private function currentVideoIds(Section $section): array
    {
        $rawIds = CourseVideo::query()
            ->forCourseId((int) $section->course_id)
            ->forSectionId((int) $section->id)
            ->notDeleted()
            ->orderBy('order_index')
            ->pluck('video_id')
            ->all();

        /** @var array<int, int> $ids */
        $ids = array_map(static fn (int|string $id): int => (int) $id, $rawIds);

        return $ids;
    }

    private function nextVideoOrder(Section $section): int
    {
        $maxOrder = CourseVideo::query()
            ->forCourseId((int) $section->course_id)
            ->forSectionId((int) $section->id)
            ->notDeleted()
            ->max('order_index');

        return is_numeric($maxOrder) ? (int) $maxOrder + 1 : 1;
    }

    /** @return array<int, int> */
    private function currentPdfIds(Section $section): array
    {
        $rawIds = CoursePdf::query()
            ->forCourseId((int) $section->course_id)
            ->forSectionId((int) $section->id)
            ->notDeleted()
            ->orderBy('order_index')
            ->pluck('pdf_id')
            ->all();

        /** @var array<int, int> $ids */
        $ids = array_map(static fn (int|string $id): int => (int) $id, $rawIds);

        return $ids;
    }

    private function nextPdfOrder(Section $section): int
    {
        $maxOrder = CoursePdf::query()
            ->forCourseId((int) $section->course_id)
            ->forSectionId((int) $section->id)
            ->notDeleted()
            ->max('order_index');

        return is_numeric($maxOrder) ? (int) $maxOrder + 1 : 1;
    }

    private function syncVideoOrderForSection(int $courseId, int $sectionId): void
    {
        $rawIds = CourseVideo::query()
            ->forCourseId($courseId)
            ->forSectionId($sectionId)
            ->notDeleted()
            ->orderBy('order_index')
            ->pluck('video_id')
            ->all();
        /** @var array<int, int> $ids */
        $ids = array_map(static fn (int|string $id): int => (int) $id, $rawIds);

        $section = new Section(['id' => $sectionId, 'course_id' => $courseId]);
        $this->syncVideoOrder($section, $ids);
    }

    private function syncPdfOrderForSection(int $courseId, int $sectionId): void
    {
        $rawIds = CoursePdf::query()
            ->forCourseId($courseId)
            ->forSectionId($sectionId)
            ->notDeleted()
            ->orderBy('order_index')
            ->pluck('pdf_id')
            ->all();
        /** @var array<int, int> $ids */
        $ids = array_map(static fn (int|string $id): int => (int) $id, $rawIds);

        $section = new Section(['id' => $sectionId, 'course_id' => $courseId]);
        $this->syncPdfOrder($section, $ids);
    }

    private function assertVideoBelongsToCourse(Section $section, Video $video): void
    {
        $attachedToOtherCourse = $this->attachmentAccessService->isVideoAttachedToOtherCourse($section, $video);

        if ($attachedToOtherCourse) {
            throw new AttachmentNotAllowedException('Video is already attached to another course.', 422);
        }
    }

    private function assertPdfBelongsToCourse(Section $section, Pdf $pdf): void
    {
        $attachedToOtherCourse = $this->attachmentAccessService->isPdfAttachedToOtherCourse($section, $pdf);

        if ($attachedToOtherCourse) {
            throw new AttachmentNotAllowedException('PDF is already attached to another course.', 422);
        }
    }

    private function assertCenterScope(Section $section, ?User $actor): void
    {
        if (! $actor instanceof User) {
            return;
        }

        $section->loadMissing('course');
        $this->centerScopeService->assertAdminSameCenter($actor, $section->course);
    }

    private function assertSectionActive(Section $section): void
    {
        if (method_exists($section, 'trashed') && $section->trashed()) {
            throw new AttachmentNotAllowedException('Section is deleted.', 422);
        }
    }
}
