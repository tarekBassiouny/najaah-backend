<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Videos;

use Illuminate\Foundation\Http\FormRequest;

class StoreVideoUploadSessionRequest extends FormRequest
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
            'original_filename' => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function bodyParameters(): array
    {
        return [
            'video_id' => [
                'description' => 'Video ID to initialize upload for.',
                'example' => 10,
            ],
            'original_filename' => [
                'description' => 'Original filename of the upload.',
                'example' => 'lecture-1.mp4',
            ],
        ];
    }
}
