<?php

declare(strict_types=1);

namespace App\Http\Requests\Mobile;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'filled', 'string'],
            'parent_phone' => ['sometimes', 'nullable', 'string', 'max:24', 'regex:/^[\d+\s().-]+$/'],
        ];
    }

    /**
     * @return array<string, array<string, string>>
     */
    public function bodyParameters(): array
    {
        return [
            'name' => [
                'description' => 'The name of the user.',
                'example' => 'John Doe',
            ],
            'parent_phone' => [
                'description' => 'Optional parent phone number for notifications.',
                'example' => '+201001234567',
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->exists('name') || $this->exists('parent_phone')) {
                return;
            }

            $validator->errors()->add('name', 'At least one profile field must be provided.');
        });
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('name')) {
            $this->merge([
                'name' => trim((string) $this->input('name')),
            ]);
        }

        if (! $this->has('parent_phone')) {
            return;
        }

        $parentPhone = preg_replace('/[^\d+]+/', '', (string) $this->input('parent_phone'));

        $this->merge([
            'parent_phone' => $parentPhone !== null && $parentPhone !== ''
                ? trim((string) $parentPhone)
                : null,
        ]);
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
