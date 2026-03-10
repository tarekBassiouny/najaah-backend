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
     * @return array<string, string>
     */
    public function bodyParameters(): array
    {
        return [
            'title_translations' => 'Assignment title in multiple languages',
            'title_translations.en' => 'English title (required)',
            'description_translations' => 'Assignment instructions (markdown supported)',
            'submission_types' => 'Allowed submission types: 0=file, 1=text, 2=link',
            'allowed_file_types' => 'Allowed file extensions, e.g., ["pdf", "doc", "docx"]',
            'max_file_size_mb' => 'Maximum file size in MB (default: 10)',
            'max_files' => 'Maximum number of files per submission (default: 5)',
            'is_group_assignment' => 'Allow group submissions (default: false)',
            'max_group_size' => 'Maximum students per group (required if is_group_assignment)',
            'max_points' => 'Maximum points for this assignment (default: 100)',
            'passing_score' => 'Minimum percentage to pass (default: 60)',
            'due_date' => 'Submission deadline',
            'late_submission_allowed' => 'Accept late submissions (default: false)',
            'late_penalty_percent' => 'Penalty per day late as percentage (default: 0)',
        ];
    }
}
