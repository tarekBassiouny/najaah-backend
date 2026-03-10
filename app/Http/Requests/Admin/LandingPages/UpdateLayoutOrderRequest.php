<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\LandingPages;

use App\Models\CenterLandingPage;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class UpdateLayoutOrderRequest extends FormRequest
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
            'section_order' => ['required', 'array', 'size:5'],
            'section_order.*' => ['required', 'string', 'distinct', Rule::in(CenterLandingPage::DEFAULT_SECTION_ORDER)],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function bodyParameters(): array
    {
        return [
            'section_order' => [
                'description' => 'Exact section render order. Must include all supported sections once.',
                'example' => ['hero', 'about', 'courses', 'testimonials', 'contact'],
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
