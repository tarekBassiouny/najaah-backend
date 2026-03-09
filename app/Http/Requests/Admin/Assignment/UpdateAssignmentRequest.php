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
}
