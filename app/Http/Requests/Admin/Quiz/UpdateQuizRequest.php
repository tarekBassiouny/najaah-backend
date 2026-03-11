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

    /**
     * @return array<string, array<string, mixed>>
     */
    public function bodyParameters(): array
    {
        return [
            'title_translations' => [
                'description' => 'Localized quiz title map.',
                'example' => ['en' => 'Updated Quiz Title', 'ar' => 'عنوان اختبار محدث'],
            ],
            'description_translations' => [
                'description' => 'Localized quiz description map.',
                'example' => ['en' => 'Updated instructions'],
            ],
            'attachable_type' => [
                'description' => 'Attachable type: video, pdf, section, or course.',
                'example' => 'video',
            ],
            'attachable_id' => [
                'description' => 'Attachable entity ID.',
                'example' => 55,
            ],
            'passing_score' => [
                'description' => 'Pass threshold percentage.',
                'example' => 75,
            ],
            'max_attempts' => [
                'description' => 'Maximum attempts, 0 for unlimited.',
                'example' => 3,
            ],
            'attempt_score_policy' => [
                'description' => 'Score policy (0=best, 1=latest, 2=average).',
                'example' => 0,
            ],
            'time_limit_minutes' => [
                'description' => 'Time limit in minutes.',
                'example' => 20,
            ],
            'shuffle_questions' => [
                'description' => 'Shuffle question order.',
                'example' => true,
            ],
            'shuffle_answers' => [
                'description' => 'Shuffle answers order.',
                'example' => true,
            ],
            'show_correct_answers' => [
                'description' => 'Show correct answers after submission.',
                'example' => true,
            ],
            'show_score_immediately' => [
                'description' => 'Show score immediately.',
                'example' => true,
            ],
            'is_required' => [
                'description' => 'Required for completion.',
                'example' => false,
            ],
            'is_active' => [
                'description' => 'Is quiz active.',
                'example' => true,
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
                'example' => 1,
            ],
        ];
    }
}
