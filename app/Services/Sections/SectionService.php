<?php

declare(strict_types=1);

namespace App\Services\Sections;

use App\Models\Section;
use App\Services\Sections\Contracts\SectionServiceInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SectionService implements SectionServiceInterface
{
    /** @return Collection<int, Section> */
    public function listForCourse(int $courseId): Collection
    {
        return Section::query()
            ->where('course_id', $courseId)
            ->orderBy('order_index')
            ->with(['videos', 'pdfs'])
            ->get();
    }

    public function find(int $id): ?Section
    {
        return Section::with(['videos', 'pdfs'])->find($id);
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): Section
    {
        $courseId = isset($data['course_id']) && is_numeric($data['course_id']) ? (int) $data['course_id'] : 0;
        $nextOrder = $this->nextOrderIndex($courseId);

        $payload = [
            ...$data,
            'order_index' => $data['order_index'] ?? $nextOrder,
        ];

        $section = Section::create($payload);

        return $section->fresh(['videos', 'pdfs']) ?? $section;
    }

    /** @param array<string, mixed> $data */
    public function update(Section $section, array $data): Section
    {
        $section->update($data);

        return $section->fresh(['videos', 'pdfs']) ?? $section;
    }

    public function delete(Section $section): void
    {
        $section->delete();
    }

    public function restore(Section $section): Section
    {
        $section->restore();

        return $section->fresh(['videos', 'pdfs']) ?? $section;
    }

    public function reorder(Section $section, int $newIndex): void
    {
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
}
