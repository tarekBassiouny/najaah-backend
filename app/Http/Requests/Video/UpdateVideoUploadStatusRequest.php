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
}
