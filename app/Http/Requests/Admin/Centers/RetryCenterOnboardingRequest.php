<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Centers;

use Illuminate\Foundation\Http\FormRequest;

class RetryCenterOnboardingRequest extends FormRequest
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
        return [];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function bodyParameters(): array
    {
        return [];
    }
}
