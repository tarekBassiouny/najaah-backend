<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\AIContent;

use App\Enums\AIContentJobStatus;
use App\Enums\AIContentTargetType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListAIContentJobsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string,mixed>
     */
    public function rules(): array
    {
        return [
            'course_id' => ['sometimes', 'integer', 'exists:courses,id'],
            'batch_key' => ['sometimes', 'uuid'],
            'status' => ['sometimes', 'integer', Rule::enum(AIContentJobStatus::class)],
            'target_type' => ['sometimes', 'string', Rule::enum(AIContentTargetType::class)],
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
            'course_id' => [
                'description' => 'Filter by course ID.',
                'example' => '12',
            ],
            'batch_key' => [
                'description' => 'Filter jobs by batch key.',
                'example' => '8c7d3f6e-8a1c-4a1f-95fb-44d9208cb3ec',
            ],
            'status' => [
                'description' => 'Filter by status enum value (0..6).',
                'example' => '2',
            ],
            'target_type' => [
                'description' => 'Filter by target type.',
                'example' => 'quiz',
            ],
            'page' => [
                'description' => 'Pagination page.',
                'example' => '1',
            ],
            'per_page' => [
                'description' => 'Pagination size (max 100).',
                'example' => '20',
            ],
        ];
    }

    /**
     * @return array<string,mixed>
     */
    public function bodyParameters(): array
    {
        return [];
    }
}
