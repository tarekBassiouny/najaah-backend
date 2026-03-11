<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Quiz;

use Illuminate\Foundation\Http\FormRequest;

class ReorderQuestionsRequest extends FormRequest
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
            'question_ids' => ['required', 'array', 'min:1'],
            'question_ids.*' => ['required', 'integer', 'exists:quiz_questions,id'],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function bodyParameters(): array
    {
        return [
            'question_ids' => [
                'description' => 'Ordered array of question IDs.',
                'example' => [13, 9, 22],
            ],
        ];
    }
}
