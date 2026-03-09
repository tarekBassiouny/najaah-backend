<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Assignment;

use App\Enums\SubmissionStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListSubmissionsRequest extends FormRequest
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
            'status' => ['sometimes', 'integer', Rule::enum(SubmissionStatus::class)],
            'user_id' => ['sometimes', 'integer', 'exists:users,id'],
            'passed' => ['sometimes', 'boolean'],
            'is_late' => ['sometimes', 'boolean'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }
}
