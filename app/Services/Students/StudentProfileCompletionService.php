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

        if ($this->requiresNameCompletion($student)) {
            $missingSteps[] = 'name';
            $missingFields[] = 'name';
        }

        $missingEducationFields = $this->missingEducationFields($student, $resolvedCenterId);

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
     * @return array<int, string>
     */
    private function missingEducationFields(User $student, ?int $resolvedCenterId): array
    {
        $settings = $this->educationProfileService->resolveSettings($student, $resolvedCenterId);

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
