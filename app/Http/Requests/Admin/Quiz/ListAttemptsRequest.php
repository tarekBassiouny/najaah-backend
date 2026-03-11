<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Quiz;

use App\Enums\QuizAttemptStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListAttemptsRequest extends FormRequest
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
            'status' => ['sometimes', 'integer', Rule::enum(QuizAttemptStatus::class)],
            'user_id' => ['sometimes', 'integer', 'exists:users,id'],
            'passed' => ['sometimes', 'boolean'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }

    /**
     * @return array<string, array<string, string>>
     */
    public function queryParameters(): array
    {
        return [
            'status' => [
                'description' => 'Attempt status filter (0..3).',
                'example' => '3',
            ],
            'user_id' => [
                'description' => 'Student user ID filter.',
                'example' => '42',
            ],
            'passed' => [
                'description' => 'Pass/fail filter.',
                'example' => 'true',
            ],
            'page' => [
                'description' => 'Page number.',
                'example' => '1',
            ],
            'per_page' => [
                'description' => 'Items per page (max 100).',
                'example' => '15',
            ],
        ];
    }
}
