<?php

declare(strict_types=1);

namespace App\Services\Students;

use App\Models\User;
use Illuminate\Support\Str;

class StudentProfileCompletionService
{
    public function __construct(
        private readonly StudentEducationProfileService $educationProfileService
    ) {}

    /**
     * @return array{
     *   is_complete_profile: bool,
     *   missing_steps: array<int, string>,
     *   missing_fields: array<int, string>
     * }
     */
    public function resolve(User $student, ?int $resolvedCenterId = null): array
    {
        $missingSteps = [];
        $missingFields = [];
        $settings = $this->educationProfileService->resolveSettings($student, $resolvedCenterId);

        if ($this->requiresNameCompletion($student)) {
            $missingSteps[] = 'name';
            $missingFields[] = 'name';
        }

        if ($this->requiresParentPhoneCompletion($student, $settings)) {
            $missingSteps[] = 'parent';
            $missingFields[] = 'parent_phone';
        }

        $missingEducationFields = $this->missingEducationFields($student, $settings);

        if ($missingEducationFields !== []) {
            $missingSteps[] = 'education';
            $missingFields = [...$missingFields, ...$missingEducationFields];
        }

        return [
            'is_complete_profile' => $missingSteps === [],
            'missing_steps' => array_values(array_unique($missingSteps)),
            'missing_fields' => array_values(array_unique($missingFields)),
        ];
    }

    private function requiresNameCompletion(User $student): bool
    {
        $name = Str::of((string) $student->name)->trim()->lower()->value();

        return $name === '' || $name === 'student';
    }

    /**
     * @param  array<string, bool>  $settings
     */
    private function requiresParentPhoneCompletion(User $student, array $settings): bool
    {
        if (($settings['enable_parent_phone'] ?? true) !== true) {
            return false;
        }

        if (($settings['require_parent_phone'] ?? false) !== true) {
            return false;
        }

        return Str::of((string) $student->parent_phone)->trim()->value() === '';
    }

    /**
     * @return array<int, string>
     */
    private function missingEducationFields(User $student, array $settings): array
    {
        $requiredFields = [
            'grade_id' => ['enable' => 'enable_grade', 'require' => 'require_grade'],
            'school_id' => ['enable' => 'enable_school', 'require' => 'require_school'],
            'college_id' => ['enable' => 'enable_college', 'require' => 'require_college'],
        ];

        $missing = [];

        foreach ($requiredFields as $field => $keys) {
            if (($settings[$keys['enable']] ?? true) !== true) {
                continue;
            }

            if (($settings[$keys['require']] ?? false) !== true) {
                continue;
            }

            if (! is_numeric($student->{$field})) {
                $missing[] = $field;
            }
        }

        return $missing;
    }
}
