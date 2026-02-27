<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Sections;

use Illuminate\Foundation\Http\FormRequest;

class BulkDetachVideosFromSectionRequest extends FormRequest
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
            'video_ids' => ['required', 'array', 'min:1', 'max:50'],
            'video_ids.*' => ['required', 'integer', 'exists:videos,id'],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function bodyParameters(): array
    {
        return [
            'video_ids' => [
                'description' => 'Array of video IDs to detach from the section.',
                'example' => [1, 2, 3],
            ],
        ];
    }
}
