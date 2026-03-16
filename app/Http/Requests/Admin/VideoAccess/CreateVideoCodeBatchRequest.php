<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\VideoAccess;

use Illuminate\Foundation\Http\FormRequest;

class CreateVideoCodeBatchRequest extends FormRequest
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
            'quantity' => ['required', 'integer', 'min:1', 'max:10000'],
            'view_limit_per_code' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function bodyParameters(): array
    {
        return [
            'quantity' => [
                'description' => 'Number of codes to generate (1-10000).',
                'example' => 100,
            ],
            'view_limit_per_code' => [
                'description' => 'Maximum views allowed per code (1-100). Defaults to 2.',
                'example' => 2,
            ],
        ];
    }
}
