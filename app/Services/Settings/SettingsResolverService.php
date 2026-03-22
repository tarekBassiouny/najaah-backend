<?php

declare(strict_types=1);

namespace App\Services\Settings;

use App\Models\Center;
use App\Models\CenterSetting;
use App\Models\Course;
use App\Models\CourseSetting;
use App\Models\StudentSetting;
use App\Models\User;
use App\Models\Video;
use App\Models\VideoSetting;
use App\Services\Settings\Contracts\SettingsResolverServiceInterface;

class SettingsResolverService implements SettingsResolverServiceInterface
{
    /** @var array<int, string> */
    private array $allowedKeys = [
        'view_limit',
        'allow_extra_view_requests',
        'requires_video_approval',
        'video_code_expiry_days',
        'pdf_download_permission',
        'device_limit',
        'allow_guest_browsing',
        'branding',
        'education_profile',
    ];

    public function __construct(
        private readonly PolicySettingsService $policySettingsService
    ) {}

    public function resolve(?User $student, ?Video $video = null, ?Course $course = null, ?Center $center = null): array
    {
        $course = $course ?? $this->resolveCourseFromVideo($video);
        $center = $center ?? $this->resolveCenter($course, $video);

        $resolved = [];

        if ($center !== null) {
            $resolved = $this->apply($resolved, $this->centerDefaults($center));
            $resolved = $this->apply($resolved, $this->filterCenterSettings($center->setting));
        }

        if ($course !== null) {
            $resolved = $this->apply($resolved, $this->filterSettings($course->setting));
        }

        if ($video !== null) {
            $resolved = $this->apply($resolved, $this->filterSettings($video->setting));
        }

        if ($student !== null) {
            $resolved = $this->apply($resolved, $this->filterSettings($student->studentSetting));
        }

        return $this->applySystemConstraints($resolved, $center);
    }

    private function resolveCourseFromVideo(?Video $video): ?Course
    {
        if ($video === null) {
            return null;
        }

        if ($video->relationLoaded('courses')) {
            return $video->courses->count() === 1 ? $video->courses->first() : null;
        }

        $courses = $video->courses()
            ->wherePivotNull('deleted_at')
            ->limit(2)
            ->get();

        return $courses->count() === 1 ? $courses->first() : null;
    }

    private function resolveCenter(?Course $course, ?Video $video): ?Center
    {
        if ($course !== null) {
            return $course->center;
        }

        if ($video !== null) {
            if ($video->relationLoaded('center') && $video->center !== null) {
                return $video->center;
            }

            if (is_numeric($video->center_id)) {
                return Center::query()->find((int) $video->center_id);
            }

            $courseFromVideo = $this->resolveCourseFromVideo($video);

            return $courseFromVideo?->center;
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $resolved
     * @param  array<string, mixed>  $incoming
     * @return array<string, mixed>
     */
    private function apply(array $resolved, array $incoming): array
    {
        foreach ($incoming as $key => $value) {
            if (! in_array($key, $this->allowedKeys, true)) {
                continue;
            }

            $resolved[$key] = $value;
        }

        return $resolved;
    }

    /**
     * @return array<string, mixed>
     */
    private function centerDefaults(Center $center): array
    {
        $defaults = [];

        if ($center->default_view_limit !== null) {
            $defaults['view_limit'] = $center->default_view_limit;
        }

        $defaults['allow_extra_view_requests'] = $center->allow_extra_view_requests;
        $defaults['requires_video_approval'] = false;
        $defaults['video_code_expiry_days'] = null;
        $defaults['pdf_download_permission'] = $center->pdf_download_permission;
        $defaults['device_limit'] = $center->device_limit;
        $defaults['allow_guest_browsing'] = $center->allow_guest_browsing;

        $branding = array_filter([
            'logo_url' => $center->logo_url,
            'primary_color' => $center->primary_color,
        ], static fn ($value): bool => $value !== null);

        if (! empty($branding)) {
            $defaults['branding'] = $branding;
        }

        return $defaults;
    }

    /**
     * @return array<string, mixed>
     */
    private function filterCenterSettings(?CenterSetting $setting): array
    {
        if ($setting === null) {
            return [];
        }

        $settings = $setting->settings ?? [];

        if (isset($settings['default_view_limit'])) {
            $settings['view_limit'] = $settings['default_view_limit'];
            unset($settings['default_view_limit']);
        }

        if (isset($settings['branding']) && ! is_array($settings['branding'])) {
            unset($settings['branding']);
        }

        return $this->filterRawSettings($settings);
    }

    /**
     * @return array<string, mixed>
     */
    private function filterSettings(StudentSetting|VideoSetting|CourseSetting|null $setting): array
    {
        if ($setting === null) {
            return [];
        }

        $settings = $setting->settings ?? [];

        return $this->filterRawSettings($settings);
    }

    /**
     * @param  array<string, mixed>  $settings
     * @return array<string, mixed>
     */
    private function filterRawSettings(array $settings): array
    {
        $filtered = [];

        foreach ($settings as $key => $value) {
            if (! in_array($key, $this->allowedKeys, true)) {
                continue;
            }

            if ($key === 'branding' && ! is_array($value)) {
                continue;
            }

            $filtered[$key] = $value;
        }

        return $filtered;
    }

    /**
     * Apply system-level constraints (ceilings and force-disable overrides).
     *
     * @param  array<string, mixed>  $resolved
     * @return array<string, mixed>
     */
    private function applySystemConstraints(array $resolved, ?Center $center = null): array
    {
        $constraints = $this->policySettingsService->systemConstraints();

        // Enforce ceiling limits
        if (isset($resolved['view_limit']) && is_numeric($resolved['view_limit'])) {
            $resolved['view_limit'] = min((int) $resolved['view_limit'], (int) $constraints['max_view_limit']);
        }

        if (isset($resolved['device_limit']) && is_numeric($resolved['device_limit'])) {
            $resolved['device_limit'] = min((int) $resolved['device_limit'], (int) $constraints['max_device_limit']);
        }

        // Enforce force-disable overrides
        if ($constraints['force_disable_extra_view_requests'] === true) {
            $resolved['allow_extra_view_requests'] = false;
        }

        if ($constraints['force_disable_pdf_download'] === true) {
            $resolved['pdf_download_permission'] = false;
        }

        if ($constraints['force_disable_guest_browsing'] === true) {
            $resolved['allow_guest_browsing'] = false;
        }

        if ($center instanceof Center) {
            $features = $this->policySettingsService->centerFeatures($center);

            if (($features['pdf_downloads'] ?? true) !== true) {
                $resolved['pdf_download_permission'] = false;
            }

            if (($features['guest_browsing'] ?? true) !== true) {
                $resolved['allow_guest_browsing'] = false;
            }

            if (($features['codes_access'] ?? true) !== true) {
                $resolved['video_code_expiry_days'] = null;
            }
        }

        return $resolved;
    }
}
