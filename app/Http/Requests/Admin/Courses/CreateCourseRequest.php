<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Courses;

use App\Models\Center;
use App\Models\College;
use App\Models\Grade;
use App\Models\School;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateCourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('difficulty') && ! $this->has('difficulty_level')) {
            $this->merge([
                'difficulty_level' => $this->mapDifficulty((string) $this->input('difficulty', '')),
            ]);
        }

        if ($this->user()?->id && ! $this->has('created_by')) {
            $this->merge([
                'created_by' => $this->user()->id,
            ]);
        }

        if ($this->has('instructor_id') && ! $this->has('primary_instructor_id')) {
            $this->merge([
                'primary_instructor_id' => $this->input('instructor_id'),
            ]);
        }

        if (! $this->has('show_for_all_students') && $this->hasAnyTargetingIds($this->all())) {
            $this->merge([
                'show_for_all_students' => false,
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $centerId = $this->resolveCenterId();

        return [
            'title_translations' => ['required', 'array', 'min:1'],
            'title_translations.en' => ['required', 'string', 'max:255'],
            'title_translations.ar' => ['nullable', 'string', 'max:255'],
            'description_translations' => ['nullable', 'array'],
            'description_translations.en' => ['nullable', 'string'],
            'description_translations.ar' => ['nullable', 'string'],
            'category_id' => ['required', 'exists:categories,id'],
            'difficulty' => ['required', 'in:beginner,intermediate,advanced'],
            'language' => ['nullable', 'string', 'max:10'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'requires_video_approval' => ['sometimes', 'nullable', 'boolean'],
            'show_for_all_students' => ['sometimes', 'boolean'],
            'grade_ids' => ['sometimes', 'array'],
            'grade_ids.*' => [
                'integer',
                'distinct',
                Rule::exists(Grade::class, 'id')->where(fn ($query) => $query->where('center_id', $centerId)),
            ],
            'school_ids' => ['sometimes', 'array'],
            'school_ids.*' => [
                'integer',
                'distinct',
                Rule::exists(School::class, 'id')->where(fn ($query) => $query->where('center_id', $centerId)),
            ],
            'college_ids' => ['sometimes', 'array'],
            'college_ids.*' => [
                'integer',
                'distinct',
                Rule::exists(College::class, 'id')->where(fn ($query) => $query->where('center_id', $centerId)),
            ],
            'metadata' => ['nullable', 'array'],
            'difficulty_level' => ['sometimes', 'integer'],
            'created_by' => ['sometimes', 'integer', 'exists:users,id'],
            'instructor_id' => ['nullable', 'integer', 'exists:instructors,id'],
            'primary_instructor_id' => ['sometimes', 'integer', 'exists:instructors,id'],
            'thumbnail' => ['sometimes', 'image', 'max:5120'],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function bodyParameters(): array
    {
        return [
            'title_translations' => [
                'description' => 'Course title translations object.',
                'example' => ['en' => 'Sample Course', 'ar' => 'دورة تجريبية'],
            ],
            'title_translations.en' => [
                'description' => 'Course title in English (required).',
                'example' => 'Sample Course',
            ],
            'title_translations.ar' => [
                'description' => 'Course title in Arabic (optional).',
                'example' => 'دورة تجريبية',
            ],
            'description_translations' => [
                'description' => 'Course description translations object.',
                'example' => ['en' => 'This is an introductory course.', 'ar' => 'هذه دورة تمهيدية.'],
            ],
            'description_translations.en' => [
                'description' => 'Course description in English.',
                'example' => 'This is an introductory course.',
            ],
            'description_translations.ar' => [
                'description' => 'Course description in Arabic.',
                'example' => 'هذه دورة تمهيدية.',
            ],
            'category_id' => [
                'description' => 'Category ID for the course.',
                'example' => 1,
            ],
            'difficulty' => [
                'description' => 'Difficulty level slug.',
                'example' => 'beginner',
            ],
            'price' => [
                'description' => 'Optional course price.',
                'example' => 0,
            ],
            'requires_video_approval' => [
                'description' => 'Optional per-course override for video access approval. Null inherits center settings.',
                'example' => true,
            ],
            'show_for_all_students' => [
                'description' => 'If true, course is visible to all students in the center. If false, education targets are used.',
                'example' => true,
            ],
            'grade_ids' => [
                'description' => 'Target grade IDs when show_for_all_students=false.',
                'example' => [12],
            ],
            'school_ids' => [
                'description' => 'Target school IDs when show_for_all_students=false.',
                'example' => [3, 5],
            ],
            'college_ids' => [
                'description' => 'Target college IDs when show_for_all_students=false.',
                'example' => [7],
            ],
            'metadata' => [
                'description' => 'Optional metadata array.',
                'example' => ['key' => 'value'],
            ],
            'instructor_id' => [
                'description' => 'Primary instructor ID for the course.',
                'example' => 5,
            ],
            'thumbnail' => [
                'description' => 'Course thumbnail image file (multipart/form-data).',
                'example' => null,
            ],
        ];
    }

    private function mapDifficulty(string $value): int
    {
        return match ($value) {
            'beginner' => 1,
            'intermediate' => 2,
            'advanced' => 3,
            default => 0,
        };
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            /** @var array<string, mixed> $data */
            $data = $this->all();

            $showForAll = array_key_exists('show_for_all_students', $data)
                ? (bool) $data['show_for_all_students']
                : ! $this->hasAnyTargetingIds($data);

            if ($showForAll) {
                return;
            }

            if (! $this->hasAnyTargetingIds($data)) {
                $validator->errors()->add(
                    'show_for_all_students',
                    'At least one of grade_ids, school_ids, or college_ids is required when show_for_all_students is false.'
                );
            }
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function hasAnyTargetingIds(array $data): bool
    {
        return $this->hasNonEmptyArray($data, 'grade_ids')
            || $this->hasNonEmptyArray($data, 'school_ids')
            || $this->hasNonEmptyArray($data, 'college_ids');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function hasNonEmptyArray(array $data, string $key): bool
    {
        return array_key_exists($key, $data) && is_array($data[$key]) && $data[$key] !== [];
    }

    private function resolveCenterId(): int
    {
        $center = $this->route('center');

        if ($center instanceof Center) {
            return (int) $center->id;
        }

        return is_numeric($center) ? (int) $center : 0;
    }
}
