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
}
