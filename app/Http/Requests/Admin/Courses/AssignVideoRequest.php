<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Courses;

use Illuminate\Foundation\Http\FormRequest;

class AssignVideoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>|string>
     */
    public function rules(): array
    {
        return [
            'video_id' => ['required', 'integer', 'exists:videos,id'],
            'order_index' => ['nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function bodyParameters(): array
    {
        return [
            'video_id' => [
                'description' => 'Video ID to attach to the course.',
                'example' => 10,
            ],
            'order_index' => [
                'description' => 'Optional position in the course.',
                'example' => 1,
            ],
        ];
    }
}
