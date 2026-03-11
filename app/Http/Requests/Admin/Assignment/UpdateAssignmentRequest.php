<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Assignment;

use App\Enums\SubmissionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAssignmentRequest extends FormRequest
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
            'title_translations' => ['sometimes', 'array'],
            'title_translations.en' => ['sometimes', 'string', 'max:255'],
            'title_translations.ar' => ['sometimes', 'string', 'max:255'],
            'description_translations' => ['sometimes', 'nullable', 'array'],
            'description_translations.en' => ['sometimes', 'string'],
            'description_translations.ar' => ['sometimes', 'string'],
            'attachable_type' => ['sometimes', 'nullable', 'string', Rule::in(['video', 'pdf', 'section', 'course'])],
            'attachable_id' => ['sometimes', 'nullable', 'integer'],
            'submission_types' => ['sometimes', 'array', 'min:1'],
            'submission_types.*' => ['integer', Rule::enum(SubmissionType::class)],
            'allowed_file_types' => ['sometimes', 'nullable', 'array'],
            'allowed_file_types.*' => ['string', 'max:20'],
            'max_file_size_mb' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'max_files' => ['sometimes', 'integer', 'min:1', 'max:20'],
            'is_group_assignment' => ['sometimes', 'boolean'],
            'max_group_size' => ['sometimes', 'nullable', 'integer', 'min:2', 'max:20'],
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
                'description' => 'Localized assignment title map.',
                'example' => ['en' => 'Updated Homework'],
            ],
            'description_translations' => [
                'description' => 'Localized assignment description/instructions.',
                'example' => ['en' => 'Updated instructions'],
            ],
            'attachable_type' => [
                'description' => 'Attachable type: video, pdf, section, or course.',
                'example' => 'section',
            ],
            'attachable_id' => [
                'description' => 'Attachable entity ID.',
                'example' => 9,
            ],
            'submission_types' => [
                'description' => 'Allowed submission types (0=file, 1=text, 2=link).',
                'example' => [0, 1],
            ],
            'allowed_file_types' => [
                'description' => 'Allowed file extensions.',
                'example' => ['pdf', 'docx'],
            ],
            'max_file_size_mb' => [
                'description' => 'Maximum file size in MB.',
                'example' => 15,
            ],
            'max_files' => [
                'description' => 'Maximum number of files allowed.',
                'example' => 5,
            ],
            'is_group_assignment' => [
                'description' => 'Whether assignment is group-based.',
                'example' => false,
            ],
            'max_group_size' => [
                'description' => 'Maximum group size.',
                'example' => 4,
            ],
            'max_points' => [
                'description' => 'Maximum points for assignment.',
                'example' => 100,
            ],
            'passing_score' => [
                'description' => 'Pass threshold percentage.',
                'example' => 60,
            ],
            'is_required' => [
                'description' => 'Required for completion.',
                'example' => true,
            ],
            'is_active' => [
                'description' => 'Whether assignment is active.',
                'example' => true,
            ],
            'due_date' => [
                'description' => 'Assignment due date.',
                'example' => '2026-03-30 23:59:59',
            ],
            'late_submission_allowed' => [
                'description' => 'Allow submissions after due date.',
                'example' => true,
            ],
            'late_penalty_percent' => [
                'description' => 'Late penalty percentage.',
                'example' => 10,
            ],
            'available_from' => [
                'description' => 'Availability start datetime.',
                'example' => '2026-03-12 10:00:00',
            ],
            'available_until' => [
                'description' => 'Availability end datetime.',
                'example' => '2026-04-01 23:59:59',
            ],
            'order_index' => [
                'description' => 'Display order index.',
                'example' => 2,
            ],
        ];
    }
}
