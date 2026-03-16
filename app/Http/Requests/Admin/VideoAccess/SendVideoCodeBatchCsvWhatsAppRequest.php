<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\VideoAccess;

use Illuminate\Foundation\Http\FormRequest;

class SendVideoCodeBatchCsvWhatsAppRequest extends FormRequest
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
            'phone_number' => ['required', 'string', 'max:24', 'regex:/^[\d+\s().-]+$/'],
            'start_sequence' => ['sometimes', 'integer', 'min:1'],
            'end_sequence' => ['sometimes', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function bodyParameters(): array
    {
        return [
            'phone_number' => [
                'description' => 'WhatsApp phone number to send the CSV export to.',
                'example' => '+201234567890',
            ],
            'start_sequence' => [
                'description' => 'Starting sequence number for export range.',
                'example' => 1,
            ],
            'end_sequence' => [
                'description' => 'Ending sequence number for export range.',
                'example' => 100,
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        $phoneNumber = preg_replace('/[^\d+]+/', '', (string) $this->input('phone_number'));

        $this->merge([
            'phone_number' => trim((string) $phoneNumber),
        ]);
    }
}
