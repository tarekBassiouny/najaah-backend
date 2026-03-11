<?php

declare(strict_types=1);

namespace App\Http\Requests\Mobile\Education;

use App\Enums\CenterType;
use App\Models\Center;
use App\Models\College;
use App\Models\Grade;
use App\Models\School;
use App\Models\User;
use App\Services\Students\StudentEducationProfileService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateEducationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'grade_id' => ['sometimes', 'nullable', 'integer'],
            'school_id' => ['sometimes', 'nullable', 'integer'],
            'college_id' => ['sometimes', 'nullable', 'integer'],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function bodyParameters(): array
    {
        return [
            'grade_id' => [
                'description' => 'Optional grade ID for the student.',
                'example' => 12,
            ],
            'school_id' => [
                'description' => 'Optional school ID for the student.',
                'example' => 8,
            ],
            'college_id' => [
                'description' => 'Optional college ID for the student.',
                'example' => 3,
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $student = $this->user();

            if (! $student instanceof User || ! $student->is_student) {
                return;
            }

            $settings = $this->educationProfileSettings($student);

            $this->validateModuleToggle($validator, 'grade_id', 'enable_grade', 'Grade module is disabled for this center.');
            $this->validateModuleToggle($validator, 'school_id', 'enable_school', 'School module is disabled for this center.');
            $this->validateModuleToggle($validator, 'college_id', 'enable_college', 'College module is disabled for this center.');

            $this->validateModuleRequired($validator, 'grade_id', (bool) ($settings['require_grade'] ?? false), 'Grade is required.');
            $this->validateModuleRequired($validator, 'school_id', (bool) ($settings['require_school'] ?? false), 'School is required.');
            $this->validateModuleRequired($validator, 'college_id', (bool) ($settings['require_college'] ?? false), 'College is required.');

            $this->validateScopedEntity($validator, 'grade_id', Grade::class, 'Selected grade is invalid or inactive.');
            $this->validateScopedEntity($validator, 'school_id', School::class, 'Selected school is invalid or inactive.');
            $this->validateScopedEntity($validator, 'college_id', College::class, 'Selected college is invalid or inactive.');
            $this->validateSingleCenterContext($validator);
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function educationProfileSettings(User $student): array
    {
        $resolvedCenterId = $this->attributes->get('resolved_center_id');

        return app(StudentEducationProfileService::class)->resolveSettings(
            $student,
            is_numeric($resolvedCenterId) ? (int) $resolvedCenterId : null
        );
    }

    private function validateModuleToggle(Validator $validator, string $field, string $toggleKey, string $message): void
    {
        $submitted = $this->exists($field);
        if (! $submitted) {
            return;
        }

        $student = $this->user();
        if (! $student instanceof User) {
            return;
        }

        $settings = $this->educationProfileSettings($student);
        if ((bool) ($settings[$toggleKey] ?? true) === false) {
            $validator->errors()->add($field, $message);
        }
    }

    private function validateModuleRequired(Validator $validator, string $field, bool $required, string $message): void
    {
        if (! $required) {
            return;
        }

        if (! $this->exists($field) || $this->input($field) === null) {
            $validator->errors()->add($field, $message);
        }
    }

    /**
     * @param  class-string<Grade|School|College>  $modelClass
     */
    private function validateScopedEntity(Validator $validator, string $field, string $modelClass, string $message): void
    {
        if (! $this->exists($field) || $this->input($field) === null) {
            return;
        }

        $student = $this->user();
        if (! $student instanceof User) {
            return;
        }

        $id = (int) $this->input($field);

        /** @var Grade|School|College|null $entity */
        $entity = $modelClass::query()
            ->where('id', $id)
            ->where('is_active', true)
            ->first();

        if ($entity === null || ! $this->matchesStudentScope($student, (int) $entity->center_id)) {
            $validator->errors()->add($field, $message);
        }
    }

    private function matchesStudentScope(User $student, int $entityCenterId): bool
    {
        if (is_numeric($student->center_id)) {
            return (int) $student->center_id === $entityCenterId;
        }

        $resolvedCenterId = $this->attributes->get('resolved_center_id');
        if (is_numeric($resolvedCenterId)) {
            $center = Center::query()->find((int) $resolvedCenterId);

            return $center instanceof Center
                && $center->type === CenterType::Unbranded
                && (int) $center->id === $entityCenterId;
        }

        return Center::query()
            ->where('id', $entityCenterId)
            ->where('type', CenterType::Unbranded->value)
            ->exists();
    }

    private function validateSingleCenterContext(Validator $validator): void
    {
        $submitted = [
            'grade_id' => $this->input('grade_id'),
            'school_id' => $this->input('school_id'),
            'college_id' => $this->input('college_id'),
        ];

        $centerIds = [];

        if ($this->exists('grade_id') && is_numeric($submitted['grade_id'])) {
            $grade = Grade::query()
                ->where('id', (int) $submitted['grade_id'])
                ->where('is_active', true)
                ->first();
            if ($grade instanceof Grade) {
                $centerIds[] = (int) $grade->center_id;
            }
        }

        if ($this->exists('school_id') && is_numeric($submitted['school_id'])) {
            $school = School::query()
                ->where('id', (int) $submitted['school_id'])
                ->where('is_active', true)
                ->first();
            if ($school instanceof School) {
                $centerIds[] = (int) $school->center_id;
            }
        }

        if ($this->exists('college_id') && is_numeric($submitted['college_id'])) {
            $college = College::query()
                ->where('id', (int) $submitted['college_id'])
                ->where('is_active', true)
                ->first();
            if ($college instanceof College) {
                $centerIds[] = (int) $college->center_id;
            }
        }

        if (count(array_unique($centerIds)) <= 1) {
            return;
        }

        $message = 'All education fields must belong to the same center.';

        foreach (['grade_id', 'school_id', 'college_id'] as $field) {
            if ($this->exists($field) && $this->input($field) !== null) {
                $validator->errors()->add($field, $message);
            }
        }
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'error' => [
                'code' => 'VALIDATION_ERROR',
                'message' => 'Validation failed',
                'details' => $validator->errors(),
            ],
        ], 422));
    }
}
