<?php

declare(strict_types=1);

namespace App\Http\Requests\Courses;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCourseRequest extends FormRequest
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
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'category_id' => ['sometimes', 'required', 'exists:categories,id'],
            'center_id' => ['sometimes', 'required', 'exists:centers,id'],
            'difficulty' => ['sometimes', 'required', 'in:beginner,intermediate,advanced'],
            'language' => ['sometimes', 'required', 'string', 'max:10'],
            'price' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'metadata' => ['sometimes', 'nullable', 'array'],
            'title_translations' => ['sometimes', 'array'],
            'title_translations.*' => ['string', 'max:255'],
            'description_translations' => ['sometimes', 'nullable', 'array'],
            'description_translations.*' => ['string'],
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
                'example' => 'Updated Course Title',
            ],
            'description' => [
                'description' => 'Course description (base locale string).',
                'example' => 'Updated description.',
            ],
            'category_id' => [
                'description' => 'Category ID for the course.',
                'example' => 2,
            ],
            'center_id' => [
                'description' => 'Center ID offering the course.',
                'example' => 1,
            ],
            'difficulty' => [
                'description' => 'Difficulty level slug.',
                'example' => 'intermediate',
            ],
            'language' => [
                'description' => 'Primary language code.',
                'example' => 'en',
            ],
            'price' => [
                'description' => 'Optional course price.',
                'example' => 10.5,
            ],
            'metadata' => [
                'description' => 'Optional metadata array.',
                'example' => ['key' => 'value'],
            ],
            'title_translations' => [
                'description' => 'Localized titles keyed by locale.',
                'example' => ['en' => 'Updated Course', 'ar' => 'دورة محدثة'],
            ],
            'description_translations' => [
                'description' => 'Localized descriptions keyed by locale.',
                'example' => ['en' => 'Updated desc', 'ar' => 'وصف محدث'],
            ],
            'difficulty_level' => [
                'description' => 'Mapped numeric difficulty (auto-set from difficulty).',
                'example' => 2,
            ],
            'created_by' => [
                'description' => 'User ID updating the course.',
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
