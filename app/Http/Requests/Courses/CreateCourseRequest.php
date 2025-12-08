<?php

declare(strict_types=1);

namespace App\Http\Requests\Courses;

use Illuminate\Foundation\Http\FormRequest;

class CreateCourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('title') && ! $this->has('title_translations')) {
            $this->merge([
                'title_translations' => ['en' => ($this->input('title', ''))],
            ]);
        }

        if ($this->has('description') && ! $this->has('description_translations')) {
            $this->merge([
                'description_translations' => ['en' => ($this->input('description', ''))],
            ]);
        }

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
    }

    /**
     * @return array<string, array<int, string>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category_id' => ['required', 'exists:categories,id'],
            'center_id' => ['required', 'exists:centers,id'],
            'difficulty' => ['required', 'in:beginner,intermediate,advanced'],
            'language' => ['required', 'string', 'max:10'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'metadata' => ['nullable', 'array'],
            'title_translations' => ['sometimes', 'array'],
            'title_translations.*' => ['string', 'max:255'],
            'description_translations' => ['sometimes', 'nullable', 'array'],
            'description_translations.*' => ['string'],
            'instructor_translations' => ['sometimes', 'nullable', 'array'],
            'instructor_translations.*' => ['string'],
            'difficulty_level' => ['sometimes', 'integer'],
            'created_by' => ['sometimes', 'integer', 'exists:users,id'],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function bodyParameters(): array
    {
        return [
            'title' => [
                'description' => 'Course title (base locale string).',
                'example' => 'Sample Course',
            ],
            'description' => [
                'description' => 'Course description (base locale string).',
                'example' => 'This is an introductory course.',
            ],
            'category_id' => [
                'description' => 'Category ID for the course.',
                'example' => 1,
            ],
            'center_id' => [
                'description' => 'Center ID offering the course.',
                'example' => 1,
            ],
            'difficulty' => [
                'description' => 'Difficulty level slug.',
                'example' => 'beginner',
            ],
            'language' => [
                'description' => 'Primary language code.',
                'example' => 'en',
            ],
            'price' => [
                'description' => 'Optional course price.',
                'example' => 0,
            ],
            'metadata' => [
                'description' => 'Optional metadata array.',
                'example' => ['key' => 'value'],
            ],
            'title_translations' => [
                'description' => 'Localized titles keyed by locale.',
                'example' => ['en' => 'Sample Course', 'ar' => 'دورة تجريبية'],
            ],
            'description_translations' => [
                'description' => 'Localized descriptions keyed by locale.',
                'example' => ['en' => 'Intro course', 'ar' => 'دورة مقدمة'],
            ],
            'instructor_translations' => [
                'description' => 'Localized instructor names keyed by locale.',
                'example' => ['en' => 'John Doe', 'ar' => 'جون دو'],
            ],
            'difficulty_level' => [
                'description' => 'Mapped numeric difficulty (auto-set from difficulty).',
                'example' => 1,
            ],
            'created_by' => [
                'description' => 'User ID creating the course.',
                'example' => 5,
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
