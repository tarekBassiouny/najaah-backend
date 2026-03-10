<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Quiz;

use Illuminate\Foundation\Http\FormRequest;

class ApproveQuestionsRequest extends FormRequest
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
            'question_indexes' => ['sometimes', 'nullable', 'array'],
            'question_indexes.*' => ['integer', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function bodyParameters(): array
    {
        return [
            'question_indexes' => 'Array of question indexes to approve (null = approve all)',
        ];
    }
}
