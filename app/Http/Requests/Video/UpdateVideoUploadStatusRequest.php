<?php

declare(strict_types=1);

namespace App\Http\Requests\Video;

use Illuminate\Foundation\Http\FormRequest;

class UpdateVideoUploadStatusRequest extends FormRequest
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
            'status' => ['required', 'string', 'in:PENDING,UPLOADING,PROCESSING,READY,FAILED'],
            'progress_percent' => ['sometimes', 'integer', 'min:0', 'max:100'],
            'source_id' => ['sometimes', 'nullable', 'string', 'max:255'],
            'source_url' => ['sometimes', 'nullable', 'string', 'max:2048'],
            'duration_seconds' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'error_message' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function bodyParameters(): array
    {
        return [
            'status' => [
                'description' => 'New upload status (PENDING, UPLOADING, PROCESSING, READY, FAILED).',
                'example' => 'READY',
            ],
            'progress_percent' => [
                'description' => 'Optional progress indicator between 0 and 100.',
                'example' => 75,
            ],
            'source_id' => [
                'description' => 'Optional Bunny video identifier when READY.',
                'example' => 'bunny-video-id',
            ],
            'source_url' => [
                'description' => 'Optional playback/source URL when READY.',
                'example' => 'https://example.com/video.mp4',
            ],
            'duration_seconds' => [
                'description' => 'Optional duration in seconds when READY.',
                'example' => 180,
            ],
            'error_message' => [
                'description' => 'Optional error details when FAILED.',
                'example' => 'Transcode failed due to invalid codec',
            ],
        ];
    }
}
