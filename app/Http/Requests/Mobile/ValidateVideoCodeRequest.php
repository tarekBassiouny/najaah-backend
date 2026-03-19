<?php

declare(strict_types=1);

namespace App\Http\Requests\Mobile;

use Illuminate\Foundation\Http\FormRequest;

class ValidateVideoCodeRequest extends FormRequest
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
            'code' => ['required', 'string', 'min:12', 'max:14', 'regex:/^[A-HJ-NP-Z2-9-]+$/i'],
            'video_id' => ['required', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function bodyParameters(): array
    {
        return [
            'code' => [
                'description' => 'The video access code to validate.',
                'example' => 'ABC2-DEF4-GHJ7',
            ],
            'video_id' => [
                'description' => 'The ID of the video the student expects to unlock.',
                'example' => 1,
            ],
        ];
    }
}
