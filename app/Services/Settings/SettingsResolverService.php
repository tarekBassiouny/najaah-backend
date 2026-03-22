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
    /** @var array<int, string>|null */
    private ?array $allowedKeys = null;

    public function __construct(
        private readonly PolicySettingsService $policySettingsService
    ) {}

    /**
     * Derive allowed resolver keys from the catalog.
     *
     * Maps catalog key `default_view_limit` to resolver key `view_limit`.
     *
     * @return array<int, string>
     */
    private function allowedKeys(): array
    {
        if ($this->allowedKeys !== null) {
            return $this->allowedKeys;
        }

        $keys = [];
        foreach ($this->policySettingsService->centerSettingsCatalog() as $key => $definition) {
            $resolverKey = $key === 'default_view_limit' ? 'view_limit' : $key;
            $keys[] = $resolverKey;
        }

        return $this->allowedKeys = $keys;
    }

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
            if (! in_array($key, $this->allowedKeys(), true)) {
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
            if (! in_array($key, $this->allowedKeys(), true)) {
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
     * Apply system-level constraints (ceilings, force-disable overrides, feature flags).
     *
     * Delegates to PolicySettingsService to avoid duplicating governance logic.
     * Handles the view_limit <-> default_view_limit key mapping transparently.
     *
     * @param  array<string, mixed>  $resolved
     * @return array<string, mixed>
     */
    private function applySystemConstraints(array $resolved, ?Center $center = null): array
    {
        // Map resolver key -> catalog key for governance
        $hasViewLimit = array_key_exists('view_limit', $resolved);
        if ($hasViewLimit) {
            $resolved['default_view_limit'] = $resolved['view_limit'];
            unset($resolved['view_limit']);
        }

        $resolved = $this->policySettingsService->applyGovernanceConstraints($resolved, $center);

        // Map back to resolver key
        if (array_key_exists('default_view_limit', $resolved)) {
            $resolved['view_limit'] = $resolved['default_view_limit'];
            unset($resolved['default_view_limit']);
        }

        return $resolved;
    }
}
