<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\VideoAccess;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class ApproveVideoAccessRequestRequest extends FormRequest
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
            'decision_reason' => ['sometimes', 'nullable', 'string', 'max:1000'],
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
            'decision_reason' => [
                'description' => 'Optional reason for approval action.',
                'example' => 'Student confirmed attendance.',
            ],
            'send_whatsapp' => [
                'description' => 'Whether to send generated access code via WhatsApp.',
                'example' => true,
            ],
            'whatsapp_format' => [
                'description' => 'WhatsApp message format when sending is enabled.',
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
