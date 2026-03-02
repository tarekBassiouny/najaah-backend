<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Sections;

use Illuminate\Foundation\Http\FormRequest;

class DetachVideoFromSectionRequest extends FormRequest
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
        return [];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function bodyParameters(): array
    {
        return [
        ];
    }
}
