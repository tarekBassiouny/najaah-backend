<?php

declare(strict_types=1);

namespace App\Services\Sections;

use App\Models\Course;
use App\Models\Pivots\CoursePdf;
use App\Models\Pivots\CourseVideo;
use App\Models\Section;
use App\Models\User;
use App\Services\Centers\CenterScopeService;
use App\Services\Sections\Contracts\SectionServiceInterface;
use App\Support\Guards\RejectNonScalarInput;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SectionService implements SectionServiceInterface
{
    /** @return Collection<int, Section> */
    public function listForCourse(int $courseId, ?User $actor = null): Collection
    {
        if ($actor instanceof User) {
            $course = Course::findOrFail($courseId);
            $this->centerScopeService->assertAdminSameCenter($actor, $course);
        }

        return Section::query()
            ->where('course_id', $courseId)
            ->orderBy('order_index')
            ->with(['videos', 'pdfs'])
            ->get();
    }

    public function find(int $id, ?User $actor = null): ?Section
    {
        $section = Section::with(['videos', 'pdfs', 'course'])->find($id);

        if ($section !== null && $actor instanceof User) {
            $this->centerScopeService->assertAdminSameCenter($actor, $section->course);
        }

        return $section;
    }

    /** @param array<string, mixed> $data */
    public function create(array $data, ?User $actor = null): Section
    {
        RejectNonScalarInput::validate($data, ['title', 'description']);
        // Support legacy 'title'/'description' fields by mapping to '_translations'
        if (array_key_exists('title', $data) && ! array_key_exists('title_translations', $data)) {
            $data['title_translations'] = $data['title'];
        }

        if (array_key_exists('description', $data) && ! array_key_exists('description_translations', $data)) {
            $data['description_translations'] = $data['description'];
        }

        unset($data['title'], $data['description']);

        $courseId = isset($data['course_id']) && is_numeric($data['course_id']) ? (int) $data['course_id'] : 0;

        if ($actor instanceof User) {
            $course = Course::findOrFail($courseId);
            $this->centerScopeService->assertAdminSameCenter($actor, $course);
        }

        $nextOrder = $this->nextOrderIndex($courseId);

        $payload = [
            ...$data,
            'order_index' => $data['order_index'] ?? $nextOrder,
        ];

        $section = Section::create($payload);

        return $section->fresh(['videos', 'pdfs']) ?? $section;
    }

    /** @param array<string, mixed> $data */
    public function update(Section $section, array $data, ?User $actor = null): Section
    {
        RejectNonScalarInput::validate($data, ['title', 'description']);
        if (array_key_exists('title', $data)) {
            $data['title_translations'] = $data['title'];
            unset($data['title']);
        }

        if (array_key_exists('description', $data)) {
            $data['description_translations'] = $data['description'];
            unset($data['description']);
        }

        if ($actor instanceof User) {
            $section->loadMissing('course');
            $this->centerScopeService->assertAdminSameCenter($actor, $section->course);
        }

        $section->update($data);

        return $section->fresh(['videos', 'pdfs']) ?? $section;
    }

    public function delete(Section $section, ?User $actor = null): void
    {
        if ($actor instanceof User) {
            $section->loadMissing('course');
            $this->centerScopeService->assertAdminSameCenter($actor, $section->course);
        }

        $section->delete();
    }

    public function restore(Section $section, ?User $actor = null): Section
    {
        if ($actor instanceof User) {
            $section->loadMissing('course');
            $this->centerScopeService->assertAdminSameCenter($actor, $section->course);
        }

        DB::transaction(function () use ($section): void {
            $section->restore();

            CourseVideo::withTrashed()
                ->where('course_id', $section->course_id)
                ->where('section_id', $section->id)
                ->restore();

            CoursePdf::withTrashed()
                ->where('course_id', $section->course_id)
                ->where('section_id', $section->id)
                ->restore();
        });

        return $section->fresh(['videos', 'pdfs']) ?? $section;
    }

    public function reorder(Section $section, int $newIndex, ?User $actor = null): void
    {
        if ($actor instanceof User) {
            $section->loadMissing('course');
            $this->centerScopeService->assertAdminSameCenter($actor, $section->course);
        }

        DB::transaction(function () use ($section, $newIndex): void {
            $sections = Section::query()
                ->where('course_id', $section->course_id)
                ->orderBy('order_index')
                ->get();

            $items = $sections->reject(fn (Section $item): bool => $item->id === $section->id)->values()->all();

            $position = max(0, min($newIndex - 1, count($items)));
            array_splice($items, $position, 0, [$section]);

            foreach (array_values($items) as $index => $item) {
                if ($item->order_index !== $index + 1) {
                    $item->order_index = $index + 1;
                    $item->save();
                }
            }
        });
    }

    private function nextOrderIndex(int $courseId): int
    {
        $maxOrder = Section::where('course_id', $courseId)->max('order_index');

        return is_numeric($maxOrder) ? (int) $maxOrder + 1 : 1;
    }

    public function __construct(private readonly CenterScopeService $centerScopeService) {}
}
