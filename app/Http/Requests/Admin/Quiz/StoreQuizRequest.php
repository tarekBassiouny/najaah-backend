<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Quiz;

use App\Enums\AttemptScorePolicy;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreQuizRequest extends FormRequest
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
            'passing_score' => ['sometimes', 'numeric', 'min:0', 'max:100'],
            'max_attempts' => ['sometimes', 'integer', 'min:0'],
            'attempt_score_policy' => ['sometimes', 'integer', Rule::enum(AttemptScorePolicy::class)],
            'time_limit_minutes' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'shuffle_questions' => ['sometimes', 'boolean'],
            'shuffle_answers' => ['sometimes', 'boolean'],
            'show_correct_answers' => ['sometimes', 'boolean'],
            'show_score_immediately' => ['sometimes', 'boolean'],
            'is_required' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
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
            'title_translations' => 'Quiz title in multiple languages',
            'title_translations.en' => 'English title (required)',
            'title_translations.ar' => 'Arabic title (optional)',
            'description_translations' => 'Quiz description/instructions',
            'attachable_type' => 'Type of content to attach quiz to: video, pdf, section, or course',
            'attachable_id' => 'ID of the attached content',
            'passing_score' => 'Minimum percentage to pass (default: 70)',
            'max_attempts' => 'Maximum attempts allowed, 0 for unlimited (default: 0)',
            'attempt_score_policy' => '0=best, 1=latest, 2=average (default: 0)',
            'time_limit_minutes' => 'Time limit in minutes, null for no limit',
            'shuffle_questions' => 'Randomize question order (default: false)',
            'shuffle_answers' => 'Randomize answer order (default: false)',
            'show_correct_answers' => 'Show correct answers after submission (default: true)',
            'show_score_immediately' => 'Show score immediately after submission (default: true)',
            'is_required' => 'Required for course completion (default: false)',
            'is_active' => 'Quiz is available to students (default: false)',
            'available_from' => 'Start availability datetime',
            'available_until' => 'End availability datetime',
            'order_index' => 'Display order (default: 0)',
        ];
    }
}
