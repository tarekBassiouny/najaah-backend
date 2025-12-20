<?php

declare(strict_types=1);

namespace App\Services\Sections;

use App\Models\Pdf;
use App\Models\Pivots\CoursePdf;
use App\Models\Pivots\CourseVideo;
use App\Models\Section;
use App\Models\User;
use App\Models\Video;
use App\Services\Sections\Contracts\SectionServiceInterface;
use App\Services\Sections\Contracts\SectionStructureServiceInterface;
use App\Services\Sections\Contracts\SectionWorkflowServiceInterface;
use Illuminate\Support\Facades\DB;

class SectionWorkflowService implements SectionWorkflowServiceInterface
{
    public function __construct(
        private readonly SectionServiceInterface $sectionService,
        private readonly SectionStructureServiceInterface $structureService,
    ) {}

    /** @param array<string, mixed> $data @param array<int, int> $videos @param array<int, int> $pdfs */
    public function createWithStructure(User $actor, array $data, array $videos = [], array $pdfs = []): Section
    {
        return DB::transaction(function () use ($actor, $data, $videos, $pdfs): Section {
            $section = $this->sectionService->create($data, $actor);

            $this->syncVideos($section, $videos, $actor);
            $this->syncPdfs($section, $pdfs, $actor);

            return $section->fresh(['videos', 'pdfs']) ?? $section;
        });
    }

    /** @param array<string, mixed> $data @param array<int, int> $videos @param array<int, int> $pdfs */
    public function updateWithStructure(User $actor, Section $section, array $data, array $videos = [], array $pdfs = []): Section
    {
        return DB::transaction(function () use ($actor, $section, $data, $videos, $pdfs): Section {
            $updated = $this->sectionService->update($section, $data, $actor);

            $this->syncVideos($updated, $videos, $actor);
            $this->syncPdfs($updated, $pdfs, $actor);

            return $updated->fresh(['videos', 'pdfs']) ?? $updated;
        });
    }

    public function deleteWithStructure(User $actor, Section $section): void
    {
        $this->sectionService->find($section->id, $actor);

        DB::transaction(function () use ($section, $actor): void {
            CourseVideo::where('course_id', $section->course_id)
                ->where('section_id', $section->id)
                ->whereNull('deleted_at')
                ->get()
                ->each(function (CourseVideo $pivot): void {
                    $pivot->delete();
                });

            CoursePdf::where('course_id', $section->course_id)
                ->where('section_id', $section->id)
                ->whereNull('deleted_at')
                ->get()
                ->each(function (CoursePdf $pivot): void {
                    $pivot->delete();
                });

            $this->sectionService->delete($section, $actor);
        });
    }

    public function publish(User $actor, Section $section): Section
    {
        $updated = $this->sectionService->update($section, ['visible' => true], $actor);

        return $updated->fresh(['videos', 'pdfs']) ?? $updated;
    }

    public function unpublish(User $actor, Section $section): Section
    {
        $updated = $this->sectionService->update($section, ['visible' => false], $actor);

        return $updated->fresh(['videos', 'pdfs']) ?? $updated;
    }

    /** @param array<int, int> $videos */
    private function syncVideos(Section $section, array $videos, User $actor): void
    {
        $currentIds = $this->structureService->listVideos($section, $actor)->pluck('id')->all();

        /** @var array<int, int> $toAttach */
        $toAttach = array_values(array_diff($videos, array_map('intval', $currentIds)));
        /** @var array<int, int> $toDetach */
        $toDetach = array_values(array_diff(array_map('intval', $currentIds), $videos));

        foreach ($toAttach as $videoId) {
            $video = Video::findOrFail($videoId);
            $this->structureService->attachVideo($section, $video, $actor);
        }

        foreach ($toDetach as $videoId) {
            $video = Video::find($videoId);
            if ($video !== null) {
                $this->structureService->detachVideo($section, $video, $actor);
            }
        }

        if ($videos !== []) {
            $this->structureService->syncVideoOrder($section, $videos);
        }
    }

    /** @param array<int, int> $pdfs */
    private function syncPdfs(Section $section, array $pdfs, User $actor): void
    {
        /** @var array<int, int> $currentIds */
        $currentIds = array_map('intval', $this->structureService->listPdfs($section, $actor)->pluck('id')->all());

        /** @var array<int, int> $toAttach */
        $toAttach = array_values(array_diff($pdfs, $currentIds));
        /** @var array<int, int> $toDetach */
        $toDetach = array_values(array_diff($currentIds, $pdfs));

        foreach ($toAttach as $pdfId) {
            $pdf = Pdf::findOrFail($pdfId);
            $this->structureService->attachPdf($section, $pdf, $actor);
        }

        foreach ($toDetach as $pdfId) {
            $pdf = Pdf::find($pdfId);
            if ($pdf !== null) {
                $this->structureService->detachPdf($section, $pdf, $actor);
            }
        }

        if ($pdfs !== []) {
            $this->structureService->syncPdfOrder($section, $pdfs);
        }
    }
}
