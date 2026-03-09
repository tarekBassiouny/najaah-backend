<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Quiz;

use App\Enums\AttemptScorePolicy;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateQuizRequest extends FormRequest
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
}
