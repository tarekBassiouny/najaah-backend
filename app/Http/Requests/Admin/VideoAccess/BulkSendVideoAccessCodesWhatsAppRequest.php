<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\VideoAccess;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class BulkSendVideoAccessCodesWhatsAppRequest extends FormRequest
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
            'code_ids' => ['required', 'array', 'min:1'],
            'code_ids.*' => ['integer', 'distinct'],
            'format' => ['required', 'string', 'in:qr_code,text_code'],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function bodyParameters(): array
    {
        return [
            'code_ids' => [
                'description' => 'Video access code IDs to send via WhatsApp.',
                'example' => [101, 102],
            ],
            'format' => [
                'description' => 'WhatsApp code delivery format.',
                'example' => 'text_code',
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
