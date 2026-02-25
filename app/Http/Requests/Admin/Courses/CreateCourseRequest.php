<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Courses;

use Illuminate\Foundation\Http\FormRequest;

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
    }

    /**
     * @return array<string, array<int, string>|string>
     */
    public function rules(): array
    {
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
}
