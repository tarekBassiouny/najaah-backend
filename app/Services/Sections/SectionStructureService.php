<?php

declare(strict_types=1);

namespace App\Services\Sections;

use App\Models\Pdf;
use App\Models\Pivots\CoursePdf;
use App\Models\Pivots\CourseVideo;
use App\Models\Section;
use App\Models\User;
use App\Models\Video;
use App\Services\Centers\CenterScopeService;
use App\Services\Sections\Contracts\SectionStructureServiceInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SectionStructureService implements SectionStructureServiceInterface
{
    public function __construct(private readonly CenterScopeService $centerScopeService) {}

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
        $this->assertVideoBelongsToCourse($section, $video);
        $this->assertVideoReady($video);

        $pivot = CourseVideo::withTrashed()
            ->where('video_id', $video->id)
            ->where('course_id', $section->course_id)
            ->first();

        if ($pivot !== null && $pivot->course_id !== $section->course_id) {
            throw ValidationException::withMessages([
                'course_id' => ['Video does not belong to this course.'],
            ]);
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
    }

    public function detachVideo(Section $section, Video $video, ?User $actor = null): void
    {
        $this->assertCenterScope($section, $actor);
        $pivot = CourseVideo::where('course_id', $section->course_id)
            ->where('video_id', $video->id)
            ->where('section_id', $section->id)
            ->first();

        if ($pivot === null) {
            return;
        }

        $pivot->section_id = null;
        $pivot->order_index = $this->nextVideoOrder($section);
        $pivot->save();

        $this->syncVideoOrder($section, $this->currentVideoIds($section));
    }

    public function attachPdf(Section $section, Pdf $pdf, ?User $actor = null): void
    {
        $this->assertCenterScope($section, $actor);
        $this->assertPdfBelongsToCourse($section, $pdf);

        $pivot = CoursePdf::withTrashed()
            ->where('pdf_id', $pdf->id)
            ->where('course_id', $section->course_id)
            ->first();

        if ($pivot !== null && $pivot->course_id !== $section->course_id) {
            throw ValidationException::withMessages([
                'course_id' => ['PDF does not belong to this course.'],
            ]);
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
                'download_permission_override' => null,
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
    }

    public function detachPdf(Section $section, Pdf $pdf, ?User $actor = null): void
    {
        $this->assertCenterScope($section, $actor);
        $pivot = CoursePdf::where('course_id', $section->course_id)
            ->where('pdf_id', $pdf->id)
            ->where('section_id', $section->id)
            ->first();

        if ($pivot === null) {
            return;
        }

        $pivot->section_id = null;
        $pivot->order_index = $this->nextPdfOrder($section);
        $pivot->save();

        $this->syncPdfOrder($section, $this->currentPdfIds($section));
    }

    /** @param array<int, int> $orderedIds */
    public function syncVideoOrder(Section $section, array $orderedIds): void
    {
        DB::transaction(function () use ($section, $orderedIds): void {
            $pivots = CourseVideo::where('course_id', $section->course_id)
                ->where('section_id', $section->id)
                ->whereNull('deleted_at')
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
            $pivots = CoursePdf::where('course_id', $section->course_id)
                ->where('section_id', $section->id)
                ->whereNull('deleted_at')
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
        $rawIds = CourseVideo::where('course_id', $section->course_id)
            ->where('section_id', $section->id)
            ->whereNull('deleted_at')
            ->orderBy('order_index')
            ->pluck('video_id')
            ->all();

        /** @var array<int, int> $ids */
        $ids = array_map(static fn (int|string $id): int => (int) $id, $rawIds);

        return $ids;
    }

    private function nextVideoOrder(Section $section): int
    {
        $maxOrder = CourseVideo::where('course_id', $section->course_id)
            ->where('section_id', $section->id)
            ->whereNull('deleted_at')
            ->max('order_index');

        return is_numeric($maxOrder) ? (int) $maxOrder + 1 : 1;
    }

    /** @return array<int, int> */
    private function currentPdfIds(Section $section): array
    {
        $rawIds = CoursePdf::where('course_id', $section->course_id)
            ->where('section_id', $section->id)
            ->whereNull('deleted_at')
            ->orderBy('order_index')
            ->pluck('pdf_id')
            ->all();

        /** @var array<int, int> $ids */
        $ids = array_map(static fn (int|string $id): int => (int) $id, $rawIds);

        return $ids;
    }

    private function nextPdfOrder(Section $section): int
    {
        $maxOrder = CoursePdf::where('course_id', $section->course_id)
            ->where('section_id', $section->id)
            ->whereNull('deleted_at')
            ->max('order_index');

        return is_numeric($maxOrder) ? (int) $maxOrder + 1 : 1;
    }

    private function syncVideoOrderForSection(int $courseId, int $sectionId): void
    {
        $rawIds = CourseVideo::where('course_id', $courseId)
            ->where('section_id', $sectionId)
            ->whereNull('deleted_at')
            ->orderBy('order_index')
            ->pluck('video_id')
            ->all();
        /** @var array<int, int> $ids */
        $ids = array_map(static fn (int|string $id): int => (int) $id, $rawIds);

        $section = new Section(['id' => (int) $sectionId, 'course_id' => (int) $courseId]);
        $this->syncVideoOrder($section, $ids);
    }

    private function syncPdfOrderForSection(int $courseId, int $sectionId): void
    {
        $rawIds = CoursePdf::where('course_id', $courseId)
            ->where('section_id', $sectionId)
            ->whereNull('deleted_at')
            ->orderBy('order_index')
            ->pluck('pdf_id')
            ->all();
        /** @var array<int, int> $ids */
        $ids = array_map(static fn (int|string $id): int => (int) $id, $rawIds);

        $section = new Section(['id' => (int) $sectionId, 'course_id' => (int) $courseId]);
        $this->syncPdfOrder($section, $ids);
    }

    private function assertVideoBelongsToCourse(Section $section, Video $video): void
    {
        $attachedToOtherCourse = CourseVideo::where('video_id', $video->id)
            ->where('course_id', '!=', $section->course_id)
            ->whereNull('deleted_at')
            ->exists();

        if ($attachedToOtherCourse) {
            throw ValidationException::withMessages([
                'course_id' => ['Video is already attached to another course.'],
            ]);
        }
    }

    private function assertVideoReady(Video $video): void
    {
        if ((int) $video->encoding_status !== 3) {
            throw ValidationException::withMessages([
                'video_id' => ['Video is not ready to be attached.'],
            ]);
        }
    }

    private function assertPdfBelongsToCourse(Section $section, Pdf $pdf): void
    {
        $attachedToOtherCourse = CoursePdf::where('pdf_id', $pdf->id)
            ->where('course_id', '!=', $section->course_id)
            ->whereNull('deleted_at')
            ->exists();

        if ($attachedToOtherCourse) {
            throw ValidationException::withMessages([
                'course_id' => ['PDF is already attached to another course.'],
            ]);
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
}
