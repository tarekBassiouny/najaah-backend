<?php

declare(strict_types=1);

namespace App\Http\Requests\Mobile\Quiz;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class SaveAnswerRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user instanceof User && $user->is_student === true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'question_id' => ['required', 'integer', 'exists:quiz_questions,id'],
            'answer_ids' => ['required', 'array', 'min:1'],
            'answer_ids.*' => ['required', 'integer', 'exists:quiz_answers,id'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function bodyParameters(): array
    {
        return [
            'question_id' => [
                'description' => 'Question ID inside the active quiz attempt.',
                'example' => 1201,
            ],
            'answer_ids' => [
                'description' => 'Selected answer IDs. For single-choice send one value; for multiple-choice send all selected IDs.',
                'example' => [3001],
            ],
            'answer_ids.*' => [
                'description' => 'Selected answer ID.',
                'example' => 3001,
            ],
        ];
    }
}
