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
     * @return array<string, string>
     */
    public function bodyParameters(): array
    {
        return [
            'question_translations' => 'Question text in multiple languages',
            'question_translations.en' => 'English question text (required)',
            'question_translations.ar' => 'Arabic question text (optional)',
            'question_type' => '0=single choice, 1=multiple choice (default: 0)',
            'explanation_translations' => 'Explanation shown after answering',
            'points' => 'Points for this question (default: 1.00)',
            'order_index' => 'Question order in the quiz',
            'is_active' => 'Include in quiz (default: true)',
            'answers' => 'Array of answer options (min 2, max 10)',
            'answers.*.answer_translations' => 'Answer text in multiple languages',
            'answers.*.is_correct' => 'Whether this is a correct answer',
            'answers.*.order_index' => 'Answer display order',
        ];
    }
}
