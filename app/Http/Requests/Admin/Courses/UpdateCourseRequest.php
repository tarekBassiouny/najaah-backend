<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Courses;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCourseRequest extends FormRequest
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
            'title_translations' => ['sometimes', 'array', 'min:1'],
            'title_translations.en' => ['nullable', 'string', 'max:255'],
            'title_translations.ar' => ['nullable', 'string', 'max:255'],
            'description_translations' => ['sometimes', 'nullable', 'array'],
            'description_translations.en' => ['nullable', 'string'],
            'description_translations.ar' => ['nullable', 'string'],
            'category_id' => ['sometimes', 'required', 'exists:categories,id'],
            'difficulty' => ['sometimes', 'required', 'in:beginner,intermediate,advanced'],
            'price' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'metadata' => ['sometimes', 'nullable', 'array'],
            'difficulty_level' => ['sometimes', 'integer'],
            'created_by' => ['sometimes', 'integer', 'exists:users,id'],
            'instructor_id' => ['sometimes', 'nullable', 'integer', 'exists:instructors,id'],
            'primary_instructor_id' => ['sometimes', 'integer', 'exists:instructors,id'],
            'thumbnail_url' => ['sometimes', 'nullable', 'string', 'url', 'max:2048'],
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
                'example' => ['en' => 'Updated Course Title', 'ar' => 'عنوان الدورة المحدث'],
            ],
            'title_translations.en' => [
                'description' => 'Course title in English.',
                'example' => 'Updated Course Title',
            ],
            'title_translations.ar' => [
                'description' => 'Course title in Arabic.',
                'example' => 'عنوان الدورة المحدث',
            ],
            'description_translations' => [
                'description' => 'Course description translations object.',
                'example' => ['en' => 'Updated description.', 'ar' => 'الوصف المحدث.'],
            ],
            'description_translations.en' => [
                'description' => 'Course description in English.',
                'example' => 'Updated description.',
            ],
            'description_translations.ar' => [
                'description' => 'Course description in Arabic.',
                'example' => 'الوصف المحدث.',
            ],
            'category_id' => [
                'description' => 'Category ID for the course.',
                'example' => 2,
            ],
            'difficulty' => [
                'description' => 'Difficulty level slug.',
                'example' => 'intermediate',
            ],
            'price' => [
                'description' => 'Optional course price.',
                'example' => 10.5,
            ],
            'metadata' => [
                'description' => 'Optional metadata array.',
                'example' => ['key' => 'value'],
            ],
            'instructor_id' => [
                'description' => 'Primary instructor ID for the course.',
                'example' => 5,
            ],
            'thumbnail_url' => [
                'description' => 'URL of the course thumbnail image.',
                'example' => 'https://example.com/thumbnails/course-1.jpg',
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
