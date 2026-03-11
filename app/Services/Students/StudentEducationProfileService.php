<?php

declare(strict_types=1);

namespace App\Services\Students;

use App\Enums\CenterType;
use App\Models\Center;
use App\Models\User;

class StudentEducationProfileService
{
    /**
     * @return array<string, bool>
     */
    public function resolveSettings(User $student, ?int $resolvedCenterId = null): array
    {
        $center = $this->resolveCenter($student, $resolvedCenterId);

        if (! $center instanceof Center) {
            return $this->defaultSettings();
        }

        $center->loadMissing('setting');

        $settings = $center->setting?->settings;
        $profile = is_array($settings['education_profile'] ?? null) ? $settings['education_profile'] : [];

        return array_merge($this->defaultSettings(), $profile);
    }

    public function resolveCenter(User $student, ?int $resolvedCenterId = null): ?Center
    {
        if (is_numeric($student->center_id)) {
            return Center::query()->find((int) $student->center_id);
        }

        if (is_numeric($resolvedCenterId)) {
            /** @var Center|null $center */
            $center = Center::query()
                ->where('type', CenterType::Unbranded->value)
                ->find($resolvedCenterId);

            return $center;
        }

        return null;
    }

    /**
     * @return array<string, bool>
     */
    private function defaultSettings(): array
    {
        return [
            'enable_grade' => true,
            'enable_school' => true,
            'enable_college' => true,
            'require_grade' => false,
            'require_school' => false,
            'require_college' => false,
        ];
    }
}
