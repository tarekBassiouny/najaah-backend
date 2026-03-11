<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Quiz;

use App\Enums\QuestionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreQuestionRequest extends FormRequest
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
            'question_translations' => ['required', 'array'],
            'question_translations.en' => ['required', 'string'],
            'question_translations.ar' => ['sometimes', 'string'],
            'question_type' => ['sometimes', 'integer', Rule::enum(QuestionType::class)],
            'explanation_translations' => ['sometimes', 'nullable', 'array'],
            'explanation_translations.en' => ['sometimes', 'string'],
            'explanation_translations.ar' => ['sometimes', 'string'],
            'points' => ['sometimes', 'numeric', 'min:0'],
            'order_index' => ['sometimes', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
            'answers' => ['required', 'array', 'min:2', 'max:10'],
            'answers.*.answer_translations' => ['required', 'array'],
            'answers.*.answer_translations.en' => ['required', 'string'],
            'answers.*.answer_translations.ar' => ['sometimes', 'string'],
            'answers.*.is_correct' => ['required', 'boolean'],
            'answers.*.order_index' => ['sometimes', 'integer', 'min:0'],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function bodyParameters(): array
    {
        return [
            'question_translations' => [
                'description' => 'Question text in multiple languages.',
                'example' => ['en' => 'What is 2 + 2?', 'ar' => 'كم يساوي 2 + 2؟'],
            ],
            'question_translations.en' => [
                'description' => 'English question text (required).',
                'example' => 'What is 2 + 2?',
            ],
            'question_translations.ar' => [
                'description' => 'Arabic question text (optional).',
                'example' => 'كم يساوي 2 + 2؟',
            ],
            'question_type' => [
                'description' => 'Question type (0=single choice, 1=multiple choice).',
                'example' => 0,
            ],
            'explanation_translations' => [
                'description' => 'Explanation shown after answering.',
                'example' => ['en' => 'Because 2 + 2 equals 4.'],
            ],
            'points' => [
                'description' => 'Points for this question.',
                'example' => 1,
            ],
            'order_index' => [
                'description' => 'Question order in the quiz.',
                'example' => 1,
            ],
            'is_active' => [
                'description' => 'Include question in quiz.',
                'example' => true,
            ],
            'answers' => [
                'description' => 'Array of answer options (min 2, max 10).',
                'example' => [
                    ['answer_translations' => ['en' => '3'], 'is_correct' => false, 'order_index' => 0],
                    ['answer_translations' => ['en' => '4'], 'is_correct' => true, 'order_index' => 1],
                ],
            ],
            'answers.*.answer_translations' => [
                'description' => 'Answer text in multiple languages.',
                'example' => ['en' => '4'],
            ],
            'answers.*.is_correct' => [
                'description' => 'Whether this is a correct answer.',
                'example' => true,
            ],
            'answers.*.order_index' => [
                'description' => 'Answer display order.',
                'example' => 1,
            ],
        ];
    }
}
