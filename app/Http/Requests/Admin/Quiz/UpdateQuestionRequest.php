<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Quiz;

use App\Enums\QuestionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateQuestionRequest extends FormRequest
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
            'question_translations' => ['sometimes', 'array'],
            'question_translations.en' => ['sometimes', 'string'],
            'question_translations.ar' => ['sometimes', 'string'],
            'question_type' => ['sometimes', 'integer', Rule::enum(QuestionType::class)],
            'explanation_translations' => ['sometimes', 'nullable', 'array'],
            'explanation_translations.en' => ['sometimes', 'string'],
            'explanation_translations.ar' => ['sometimes', 'string'],
            'points' => ['sometimes', 'numeric', 'min:0'],
            'order_index' => ['sometimes', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
            'answers' => ['sometimes', 'array', 'min:2', 'max:10'],
            'answers.*.answer_translations' => ['required_with:answers', 'array'],
            'answers.*.answer_translations.en' => ['required_with:answers', 'string'],
            'answers.*.answer_translations.ar' => ['sometimes', 'string'],
            'answers.*.is_correct' => ['required_with:answers', 'boolean'],
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
                'description' => 'Localized question text map.',
                'example' => ['en' => 'Updated question text?'],
            ],
            'question_type' => [
                'description' => 'Question type (0=single choice, 1=multiple choice).',
                'example' => 0,
            ],
            'explanation_translations' => [
                'description' => 'Localized explanation map.',
                'example' => ['en' => 'Updated explanation'],
            ],
            'points' => [
                'description' => 'Question points.',
                'example' => 2,
            ],
            'order_index' => [
                'description' => 'Question order in quiz.',
                'example' => 3,
            ],
            'is_active' => [
                'description' => 'Whether question is active.',
                'example' => true,
            ],
            'answers' => [
                'description' => 'Optional full replacement list of answers.',
                'example' => [
                    ['answer_translations' => ['en' => 'A'], 'is_correct' => false, 'order_index' => 0],
                    ['answer_translations' => ['en' => 'B'], 'is_correct' => true, 'order_index' => 1],
                ],
            ],
        ];
    }
}
