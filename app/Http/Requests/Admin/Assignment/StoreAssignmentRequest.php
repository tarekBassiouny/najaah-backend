<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Assignment;

use App\Enums\SubmissionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAssignmentRequest extends FormRequest
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
            'title_translations' => ['required', 'array'],
            'title_translations.en' => ['required', 'string', 'max:255'],
            'title_translations.ar' => ['sometimes', 'string', 'max:255'],
            'description_translations' => ['sometimes', 'nullable', 'array'],
            'description_translations.en' => ['sometimes', 'string'],
            'description_translations.ar' => ['sometimes', 'string'],
            'attachable_type' => ['sometimes', 'nullable', 'string', Rule::in(['video', 'pdf', 'section', 'course'])],
            'attachable_id' => ['required_with:attachable_type', 'nullable', 'integer'],
            'submission_types' => ['sometimes', 'array', 'min:1'],
            'submission_types.*' => ['integer', Rule::enum(SubmissionType::class)],
            'allowed_file_types' => ['sometimes', 'nullable', 'array'],
            'allowed_file_types.*' => ['string', 'max:20'],
            'max_file_size_mb' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'max_files' => ['sometimes', 'integer', 'min:1', 'max:20'],
            'is_group_assignment' => ['sometimes', 'boolean'],
            'max_group_size' => ['required_if:is_group_assignment,true', 'nullable', 'integer', 'min:2', 'max:20'],
            'max_points' => ['sometimes', 'numeric', 'min:1'],
            'passing_score' => ['sometimes', 'numeric', 'min:0', 'max:100'],
            'is_required' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
            'due_date' => ['sometimes', 'nullable', 'date'],
            'late_submission_allowed' => ['sometimes', 'boolean'],
            'late_penalty_percent' => ['sometimes', 'numeric', 'min:0', 'max:100'],
            'available_from' => ['sometimes', 'nullable', 'date'],
            'available_until' => ['sometimes', 'nullable', 'date', 'after_or_equal:available_from'],
            'order_index' => ['sometimes', 'integer', 'min:0'],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function bodyParameters(): array
    {
        return [
            'title_translations' => [
                'description' => 'Assignment title in multiple languages.',
                'example' => ['en' => 'Week 1 Homework', 'ar' => 'واجب الأسبوع الأول'],
            ],
            'title_translations.en' => [
                'description' => 'English title (required).',
                'example' => 'Week 1 Homework',
            ],
            'description_translations' => [
                'description' => 'Assignment instructions (markdown supported).',
                'example' => ['en' => 'Submit a PDF or text answer.'],
            ],
            'submission_types' => [
                'description' => 'Allowed submission types: 0=file, 1=text, 2=link.',
                'example' => [0, 1],
            ],
            'allowed_file_types' => [
                'description' => 'Allowed file extensions.',
                'example' => ['pdf', 'docx'],
            ],
            'max_file_size_mb' => [
                'description' => 'Maximum file size in MB.',
                'example' => 10,
            ],
            'max_files' => [
                'description' => 'Maximum number of files per submission.',
                'example' => 3,
            ],
            'is_group_assignment' => [
                'description' => 'Allow group submissions.',
                'example' => false,
            ],
            'max_group_size' => [
                'description' => 'Maximum students per group (required if group assignment).',
                'example' => 4,
            ],
            'max_points' => [
                'description' => 'Maximum points for this assignment.',
                'example' => 100,
            ],
            'passing_score' => [
                'description' => 'Minimum percentage to pass.',
                'example' => 60,
            ],
            'due_date' => [
                'description' => 'Submission deadline datetime.',
                'example' => '2026-03-30 23:59:59',
            ],
            'late_submission_allowed' => [
                'description' => 'Accept late submissions.',
                'example' => true,
            ],
            'late_penalty_percent' => [
                'description' => 'Penalty per day late as percentage.',
                'example' => 10,
            ],
        ];
    }
}
