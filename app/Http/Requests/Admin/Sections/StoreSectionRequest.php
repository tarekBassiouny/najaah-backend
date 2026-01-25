<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Sections;

use Illuminate\Foundation\Http\FormRequest;

class StoreSectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('sort_order') && ! $this->has('order_index')) {
            $this->merge([
                'order_index' => $this->input('sort_order'),
            ]);
        }

        if (! $this->has('course_id') && $this->route('course')) {
            $course = $this->route('course');
            $courseId = null;

            if ($course instanceof \App\Models\Course) {
                $courseId = $course->getKey();
            } elseif (is_numeric($course)) {
                $courseId = (int) $course;
            }

            if ($courseId !== null) {
                $this->merge([
                    'course_id' => $courseId,
                ]);
            }
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
            'order_index' => ['nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function bodyParameters(): array
    {
        return [
            'title_translations' => [
                'description' => 'Section title translations object.',
                'example' => ['en' => 'Introduction', 'ar' => 'مقدمة'],
            ],
            'title_translations.en' => [
                'description' => 'Section title in English (required).',
                'example' => 'Introduction',
            ],
            'title_translations.ar' => [
                'description' => 'Section title in Arabic (optional).',
                'example' => 'مقدمة',
            ],
            'description_translations' => [
                'description' => 'Section description translations object.',
                'example' => ['en' => 'Overview of the course.', 'ar' => 'نظرة عامة على الدورة.'],
            ],
            'order_index' => [
                'description' => 'Optional ordering index.',
                'example' => 1,
            ],
        ];
    }
}
