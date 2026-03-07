<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\VideoAccess;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class GenerateVideoAccessCodeRequest extends FormRequest
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
            'video_id' => ['required', 'integer', 'exists:videos,id'],
            'course_id' => ['required', 'integer', 'exists:courses,id'],
            'send_whatsapp' => ['sometimes', 'boolean'],
            'whatsapp_format' => ['required_if:send_whatsapp,true', 'string', 'in:qr_code,text_code'],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function bodyParameters(): array
    {
        return [
            'video_id' => [
                'description' => 'Target video ID.',
                'example' => 55,
            ],
            'course_id' => [
                'description' => 'Target course ID containing the selected video.',
                'example' => 10,
            ],
            'send_whatsapp' => [
                'description' => 'Whether to send the generated code immediately via WhatsApp.',
                'example' => true,
            ],
            'whatsapp_format' => [
                'description' => 'WhatsApp payload type when sending is enabled.',
                'example' => 'qr_code',
            ],
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'error' => [
                'code' => 'VALIDATION_ERROR',
                'message' => 'Validation failed',
                'details' => $validator->errors(),
            ],
        ], 422));
    }
}
