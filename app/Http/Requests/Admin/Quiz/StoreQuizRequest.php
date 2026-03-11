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
     * @return array<string, array<string, mixed>>
     */
    public function bodyParameters(): array
    {
        return [
            'title_translations' => [
                'description' => 'Quiz title in multiple languages.',
                'example' => ['en' => 'Chapter 1 Quiz', 'ar' => 'اختبار الفصل الأول'],
            ],
            'title_translations.en' => [
                'description' => 'English title (required).',
                'example' => 'Chapter 1 Quiz',
            ],
            'title_translations.ar' => [
                'description' => 'Arabic title (optional).',
                'example' => 'اختبار الفصل الأول',
            ],
            'description_translations' => [
                'description' => 'Quiz description/instructions.',
                'example' => ['en' => 'Read carefully before submitting.'],
            ],
            'attachable_type' => [
                'description' => 'Type of content to attach quiz to.',
                'example' => 'video',
            ],
            'attachable_id' => [
                'description' => 'ID of the attached content.',
                'example' => 55,
            ],
            'passing_score' => [
                'description' => 'Minimum percentage to pass.',
                'example' => 70,
            ],
            'max_attempts' => [
                'description' => 'Maximum attempts allowed, 0 for unlimited.',
                'example' => 3,
            ],
            'attempt_score_policy' => [
                'description' => 'Score policy (0=best, 1=latest, 2=average).',
                'example' => 0,
            ],
            'time_limit_minutes' => [
                'description' => 'Time limit in minutes, null for no limit.',
                'example' => 30,
            ],
            'shuffle_questions' => [
                'description' => 'Randomize question order.',
                'example' => true,
            ],
            'shuffle_answers' => [
                'description' => 'Randomize answer order.',
                'example' => true,
            ],
            'show_correct_answers' => [
                'description' => 'Show correct answers after submission.',
                'example' => true,
            ],
            'show_score_immediately' => [
                'description' => 'Show score immediately after submission.',
                'example' => true,
            ],
            'is_required' => [
                'description' => 'Whether quiz is required for course completion.',
                'example' => false,
            ],
            'is_active' => [
                'description' => 'Whether quiz is available to students.',
                'example' => true,
            ],
            'available_from' => [
                'description' => 'Start availability datetime.',
                'example' => '2026-03-12 10:00:00',
            ],
            'available_until' => [
                'description' => 'End availability datetime.',
                'example' => '2026-04-01 23:59:59',
            ],
            'order_index' => [
                'description' => 'Display order.',
                'example' => 0,
            ],
        ];
    }
}
