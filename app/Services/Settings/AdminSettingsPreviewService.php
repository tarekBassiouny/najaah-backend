<?php

declare(strict_types=1);

namespace App\Services\Settings;

use App\Models\Center;
use App\Models\Course;
use App\Models\User;
use App\Models\Video;
use App\Services\Centers\CenterScopeService;
use App\Services\Settings\Contracts\SettingsResolverServiceInterface;

class AdminSettingsPreviewService
{
    public function __construct(
        private readonly SettingsResolverServiceInterface $resolver,
        private readonly CenterScopeService $centerScopeService
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function resolve(User $admin, ?User $student, ?Video $video = null, ?Course $course = null, ?Center $center = null): array
    {
        if ($student !== null) {
            $this->centerScopeService->assertAdminSameCenter($admin, $student);
        }

        if ($course !== null) {
            $this->centerScopeService->assertAdminSameCenter($admin, $course);
        }

        if ($center !== null) {
            $this->centerScopeService->assertAdminSameCenter($admin, $center);
        }

        if ($video !== null) {
            $video->loadMissing(['creator', 'courses']);
            $centerId = $video->center_id ?? $video->creator->center_id;

            if ($centerId === null) {
                $courseIds = $video->courses->pluck('center_id')->filter()->unique()->values();
                $centerId = $courseIds->count() === 1 ? $courseIds->first() : null;
            }

            $this->centerScopeService->assertAdminCenterId($admin, is_numeric($centerId) ? (int) $centerId : null);
        }

        return $this->resolver->resolve($student, $video, $course, $center);
    }
}
