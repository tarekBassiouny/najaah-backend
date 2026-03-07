<?php

declare(strict_types=1);

namespace App\Http\Resources\Concerns;

use App\Models\Center;
use App\Models\CenterSetting;
use App\Models\Course;

trait ResolvesCourseRequiresVideoApproval
{
    protected function resolveRequiresVideoApproval(Course $course): bool
    {
        if ($course->requires_video_approval !== null) {
            return (bool) $course->requires_video_approval;
        }

        $center = $course->relationLoaded('center')
            ? $course->center
            : $course->center()->first();

        if (! $center instanceof Center) {
            return false;
        }

        /** @var CenterSetting|null $setting */
        $setting = $center->relationLoaded('setting')
            ? $center->setting
            : $center->setting()->first();

        $settings = $setting instanceof CenterSetting && is_array($setting->settings)
            ? $setting->settings
            : [];

        return (bool) ($settings['requires_video_approval'] ?? false);
    }
}
