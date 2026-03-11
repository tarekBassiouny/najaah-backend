<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Assignment;

use Illuminate\Foundation\Http\FormRequest;

class GradeSubmissionRequest extends FormRequest
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
            'score' => ['required', 'numeric', 'min:0'],
            'feedback' => ['sometimes', 'nullable', 'string', 'max:5000'],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function bodyParameters(): array
    {
        return [
            'score' => [
                'description' => 'Points awarded for this submission.',
                'example' => 85,
            ],
            'feedback' => [
                'description' => 'Feedback for the student (optional, max 5000 characters).',
                'example' => 'Great structure, improve the conclusion.',
            ],
        ];
    }
}
