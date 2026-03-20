<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Videos;

use Illuminate\Foundation\Http\FormRequest;

class UploadTranscriptRequest extends FormRequest
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
            'file' => ['nullable', 'file', 'mimes:txt,vtt,srt', 'max:5120', 'required_without:transcript_text'],
            'transcript_text' => ['nullable', 'string', 'max:200000', 'required_without:file'],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function bodyParameters(): array
    {
        return [
            'file' => [
                'description' => 'Transcript file upload. Accepted formats: txt, vtt, srt.',
                'type' => 'file',
                'example' => null,
            ],
            'transcript_text' => [
                'description' => 'Raw transcript text. Required when no file is uploaded.',
                'example' => 'Welcome to lesson one. Today we will cover algebra basics.',
            ],
        ];
    }
}
