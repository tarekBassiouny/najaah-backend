<?php

declare(strict_types=1);

namespace App\Http\Requests\Video;

use Illuminate\Foundation\Http\FormRequest;

class StoreVideoUploadRequest extends FormRequest
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
            'center_id' => ['required', 'integer', 'exists:centers,id'],
            'video_id' => ['sometimes', 'integer', 'exists:videos,id'],
            'original_filename' => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function bodyParameters(): array
    {
        return [
            'center_id' => [
                'description' => 'Center ID to associate the upload with.',
                'example' => 1,
            ],
            'video_id' => [
                'description' => 'Optional existing video to attach this upload session to.',
                'example' => 10,
            ],
            'original_filename' => [
                'description' => 'Original filename of the uploaded video.',
                'example' => 'lecture-1.mp4',
            ],
        ];
    }
}
