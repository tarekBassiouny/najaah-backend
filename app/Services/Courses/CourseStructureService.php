<?php

declare(strict_types=1);

namespace App\Services\Courses;

use App\Models\Course;
use App\Models\Section;
use App\Models\User;
use App\Services\Centers\CenterScopeService;
use App\Services\Courses\Contracts\CourseStructureServiceInterface;
use Illuminate\Support\Facades\DB;

class CourseStructureService implements CourseStructureServiceInterface
{
    public function __construct(private readonly CenterScopeService $centerScopeService) {}

    /** @param array<string, mixed> $data */
    public function addSection(Course $course, array $data, User $actor): Section
    {
        $this->centerScopeService->assertAdminSameCenter($actor, $course);
        $maxOrder = $course->sections()->max('order_index');
        $nextOrder = is_numeric($maxOrder) ? (int) $maxOrder + 1 : 1;

        $section = $course->sections()->create([
            ...$data,
            'order_index' => $data['order_index'] ?? $nextOrder,
        ]);

        return $section->fresh(['videos', 'pdfs']) ?? $section;
    }

    /** @param array<int, int> $orderedIds */
    public function reorderSections(Course $course, array $orderedIds, User $actor): void
    {
        $this->centerScopeService->assertAdminSameCenter($actor, $course);
        DB::transaction(function () use ($course, $orderedIds): void {
            $sections = $course->sections()->whereIn('id', $orderedIds)->get()->keyBy('id');

            foreach (array_values($orderedIds) as $index => $sectionId) {
                if (! $sections->has($sectionId)) {
                    continue;
                }

                /** @var Section $section */
                $section = $sections->get($sectionId);
                $section->order_index = $index + 1;
                $section->save();
            }
        });
    }

    public function toggleSectionVisibility(Section $section, User $actor): Section
    {
        $section->loadMissing('course');
        $this->centerScopeService->assertAdminSameCenter($actor, $section->course);
        $section->visible = ! $section->visible;
        $section->save();

        return $section;
    }
}
