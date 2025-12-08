<?php

declare(strict_types=1);

namespace App\Http\Requests\Sections;

use Illuminate\Foundation\Http\FormRequest;

class StoreSectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (! $this->has('course_id') && $this->route('course')) {
            $course = $this->route('course');
            $this->merge([
                'course_id' => is_object($course) && property_exists($course, 'id') ? (int) $course->id : (int) $course,
            ]);
        }
    }

    /**
     * @return array<string, array<int, string>|string>
     */
    public function rules(): array
    {
        return [
            'course_id' => ['required', 'integer', 'exists:courses,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer'],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function bodyParameters(): array
    {
        return [
            'course_id' => [
                'description' => 'ID of the parent course.',
                'example' => 1,
            ],
            'title' => [
                'description' => 'Section title (base locale string).',
                'example' => 'Introduction',
            ],
            'description' => [
                'description' => 'Section description (base locale string).',
                'example' => 'Overview of the course.',
            ],
            'sort_order' => [
                'description' => 'Optional ordering index.',
                'example' => 1,
            ],
        ];
    }
}
