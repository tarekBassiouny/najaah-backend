<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\VideoAccess;

use Illuminate\Foundation\Http\FormRequest;

class ListVideoCodeBatchesRequest extends FormRequest
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
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'video_id' => ['sometimes', 'integer'],
            'course_id' => ['sometimes', 'integer'],
            'status' => ['sometimes', 'in:open,closed'],
            'search' => ['sometimes', 'string'],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function queryParameters(): array
    {
        return [
            'page' => [
                'description' => 'Page number for pagination.',
                'example' => 1,
            ],
            'per_page' => [
                'description' => 'Number of items per page (max 100).',
                'example' => 20,
            ],
            'video_id' => [
                'description' => 'Filter by video ID.',
                'example' => 123,
            ],
            'course_id' => [
                'description' => 'Filter by course ID.',
                'example' => 456,
            ],
            'status' => [
                'description' => 'Filter by batch status.',
                'example' => 'open',
            ],
            'search' => [
                'description' => 'Search by batch code, video title, or course title.',
                'example' => 'ABC123',
            ],
        ];
    }
}
