<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\VideoAccess;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class BulkApproveVideoAccessRequestsRequest extends FormRequest
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
            'request_ids' => ['required', 'array', 'min:1'],
            'request_ids.*' => ['integer', 'distinct'],
            'decision_reason' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'send_whatsapp' => ['sometimes', 'boolean'],
            'whatsapp_format' => ['sometimes', 'string', 'in:qr_code,text_code'],
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
