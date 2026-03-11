<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\AI;

use Illuminate\Foundation\Http\FormRequest;

class ListCenterAIOptionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string,mixed>
     */
    public function rules(): array
    {
        return [
            'enabled_only' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function queryParameters(): array
    {
        return [
            'enabled_only' => [
                'description' => 'If true, returns only providers that are enabled and configured.',
                'example' => true,
            ],
        ];
    }

    /**
     * @return array<string,mixed>
     */
    public function bodyParameters(): array
    {
        return [];
    }

    protected function prepareForValidation(): void
    {
        $value = $this->input('enabled_only');
        if (! is_string($value)) {
            return;
        }

        $normalized = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if ($normalized === null) {
            return;
        }

        $this->merge(['enabled_only' => $normalized]);
    }
}
